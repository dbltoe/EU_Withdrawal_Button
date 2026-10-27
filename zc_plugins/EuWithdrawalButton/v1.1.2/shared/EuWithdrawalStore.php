<?php
/**
 * EU Withdrawal Button -- the database side: the customer's orders, matching a
 * statement to an order, saving it, the order's history, and the admin list.
 *
 * Every SELECT goes through EuWithdrawalCore::fresh() (core memoizes SELECTs
 * per request and a write doesn't clear the cache). Queries stay plain -- no
 * joins, no SQL date functions -- so the harness's FakeDb runs the very same
 * statements.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// Guarded: on a version upgrade the old version's files may already have
// loaded this class from the other version folder ("Cannot redeclare class").
if (!class_exists('EuWithdrawalCore', false)) {
    require_once __DIR__ . '/EuWithdrawalCore.php';
}

class EuWithdrawalStore
{
    public const PER_PAGE = 25;

    /** @var object queryFactory (or the harness's FakeDb) */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        EuWithdrawalCore::defineTables();
    }

    /**
     * The logged-in customer's id, or 0. One Page Checkout's guests share one
     * placeholder customer (CHECKOUT_ONE_GUEST_CUSTOMER_ID), whose orders are
     * every guest's: a guest session is never treated as that customer.
     */
    public static function realCustomerId($sessionCustomerId): int
    {
        $cid = (int)$sessionCustomerId;
        $guest = defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID') ? (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID : 0;
        return ($cid > 0 && $cid !== $guest) ? $cid : 0;
    }

    /** Name and email for pre-filling the form. */
    public function customer(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['name' => '', 'email' => ''];
        }
        $r = EuWithdrawalCore::fresh($this->db, "SELECT customers_firstname, customers_lastname, customers_email_address FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . $customerId);
        if ($r->EOF) {
            return ['name' => '', 'email' => ''];
        }
        return [
            'name' => trim($r->fields['customers_firstname'] . ' ' . $r->fields['customers_lastname']),
            'email' => (string)$r->fields['customers_email_address'],
        ];
    }

    /**
     * The customer's orders from the last $days days, newest first, keyed by
     * order id. $now is the store's current time (Y-m-d H:i:s); date_purchased
     * is stored in the store's own time too.
     */
    public function recentOrders(int $customerId, int $days, string $now): array
    {
        if ($customerId <= 0) {
            return [];
        }
        $cutoff = date('Y-m-d H:i:s', strtotime($now) - max(1, $days) * 86400);
        $r = EuWithdrawalCore::fresh(
            $this->db,
            "SELECT orders_id, date_purchased, order_total, currency, currency_value FROM " . TABLE_ORDERS
            . " WHERE customers_id = " . $customerId . " AND date_purchased >= '" . $cutoff . "' ORDER BY orders_id DESC LIMIT 50"
        );
        $out = [];
        while (!$r->EOF) {
            $out[(int)$r->fields['orders_id']] = $r->fields;
            $r->MoveNext();
        }
        return $out;
    }

    /** The order number in what someone typed ("#29", "Order 29", "29"), or 0. */
    public static function orderNumber(string $entered): int
    {
        return preg_match('/^\D*(\d{1,10})\D*$/', trim($entered), $m) === 1 ? (int)$m[1] : 0;
    }

    /**
     * The order a statement is for, or 0. A logged-in customer's own order
     * matches by number; anyone else's matches when the number and the email
     * agree (case doesn't matter). A mismatch is never refused: the caller saves
     * the statement as "not matched".
     */
    public function matchOrder(string $entered, string $email, int $customerId): int
    {
        $orderId = self::orderNumber($entered);
        if ($orderId <= 0) {
            return 0;
        }
        $r = EuWithdrawalCore::fresh($this->db, "SELECT orders_id, customers_id, customers_email_address FROM " . TABLE_ORDERS . " WHERE orders_id = " . $orderId);
        if ($r->EOF) {
            return 0;
        }
        if ($customerId > 0 && (int)$r->fields['customers_id'] === $customerId) {
            return $orderId;
        }
        return strcasecmp(trim((string)$r->fields['customers_email_address']), trim($email)) === 0 ? $orderId : 0;
    }

    /**
     * The visitor's country for the Show Withdrawal Button To rule, and where it
     * came from, first answer wins (spike S7): the order page's own order
     * (delivery, else billing country), the logged-in customer's default
     * address, the server variable the store names, else unknown ('').
     *
     * @return array{0:string, 1:string} ISO code or '', source
     */
    public function visitorCountry(int $customerId, string $pageBase, int $orderId, array $server, string $variable): array
    {
        if ($customerId > 0 && $pageBase === 'account_history_info' && $orderId > 0) {
            $o = EuWithdrawalCore::fresh($this->db, "SELECT delivery_country, billing_country FROM " . TABLE_ORDERS . " WHERE orders_id = " . $orderId . " AND customers_id = " . $customerId);
            if (!$o->EOF) {
                foreach (['delivery_country' => 'order delivery', 'billing_country' => 'order billing'] as $column => $source) {
                    $code = $this->countryCodeByName((string)$o->fields[$column]);
                    if ($code !== '') {
                        return [$code, $source];
                    }
                }
            }
        }
        if ($customerId > 0) {
            $code = $this->defaultAddressCountry($customerId);
            if ($code !== '') {
                return [$code, 'default address'];
            }
        }
        $code = EuWithdrawalCore::countryFromServer($server, $variable);
        return $code !== '' ? [$code, $variable] : ['', 'unknown'];
    }

    /** Orders keep the country's name, not its code (every version 1.5.8 -> 3.0.0). An unknown name gives ''. */
    public function countryCodeByName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        $r = EuWithdrawalCore::fresh($this->db, "SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " WHERE countries_name = '" . $this->db->prepare_input($name) . "'");
        return $r->EOF ? '' : strtoupper((string)$r->fields['countries_iso_code_2']);
    }

    public function defaultAddressCountry(int $customerId): string
    {
        $c = EuWithdrawalCore::fresh($this->db, "SELECT customers_default_address_id FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . $customerId);
        if ($c->EOF) {
            return '';
        }
        $a = EuWithdrawalCore::fresh($this->db, "SELECT entry_country_id FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = " . (int)$c->fields['customers_default_address_id'] . " AND customers_id = " . $customerId);
        if ($a->EOF) {
            return '';
        }
        $k = EuWithdrawalCore::fresh($this->db, "SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " WHERE countries_id = " . (int)$a->fields['entry_country_id']);
        return $k->EOF ? '' : strtoupper((string)$k->fields['countries_iso_code_2']);
    }

    /* ----------------------------------------------------------------- *
     * Statements
     * ----------------------------------------------------------------- */

    /**
     * Save a statement and return it as stored (read back past the query
     * cache), or null when the insert failed. Saved before any email is sent,
     * so a mail failure never loses a withdrawal.
     */
    public function save(array $s): ?array
    {
        // The page passes the time it also formatted for the customer, so the
        // stored UTC and the local time shown come from one reading.
        $now = isset($s['created_utc']) && preg_match('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string)$s['created_utc']) === 1
            ? (string)$s['created_utc'] : gmdate('Y-m-d H:i:s');
        // One line each: zen_mail() refuses to send to a name or address with a
        // line break, which a crafted form post could otherwise use to stop the
        // acknowledgment.
        foreach (['name', 'email', 'order_entered'] as $field) {
            $s[$field] = trim(preg_replace('/[\r\n\t]+/', ' ', (string)$s[$field]));
        }
        $row = [
            'created_utc' => $now,
            'created_local' => (string)$s['created_local'],
            'timezone' => (string)$s['timezone'],
            'customers_id' => (int)$s['customers_id'],
            'name' => EuWithdrawalCore::cut((string)$s['name'], 128),
            'email' => EuWithdrawalCore::cut((string)$s['email'], 96),
            'order_entered' => EuWithdrawalCore::cut((string)$s['order_entered'], 64),
            'orders_id' => (int)$s['orders_id'],
            'matched' => (int)$s['orders_id'] > 0 ? 1 : 0,
            'items_text' => (string)$s['items_text'],
            'language' => EuWithdrawalCore::cut((string)$s['language'], 32),
            'ip' => substr((string)$s['ip'], 0, 45),
            'country' => substr((string)$s['country'], 0, 2),
            'status' => !empty($s['held']) ? 'held' : 'received',
            'ack_sent_utc' => EuWithdrawalCore::NEVER,
            'ack_error' => '',
            'osh_id' => 0,
            'admin_note' => '',
            'updated_utc' => $now,
        ];
        $cols = [];
        $vals = [];
        foreach ($row as $col => $value) {
            $cols[] = $col;
            $vals[] = is_int($value) ? (string)$value : "'" . $this->db->prepare_input($value) . "'";
        }
        $this->db->Execute("INSERT INTO " . TABLE_EU_WITHDRAWALS . " (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
        $id = (int)$this->db->Insert_ID();
        return $id > 0 ? $this->find($id) : null;
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $r = EuWithdrawalCore::fresh($this->db, "SELECT * FROM " . TABLE_EU_WITHDRAWALS . " WHERE eu_withdrawals_id = " . $id);
        return $r->EOF ? null : $r->fields;
    }

    /** Record how the acknowledgment went: '' sent, else zen_mail()'s error. */
    public function markAcknowledged(int $id, string $error): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $set = $error === ''
            ? "ack_sent_utc = '" . $now . "', ack_error = ''"
            : "ack_error = '" . $this->db->prepare_input(substr($error, 0, 255)) . "'";
        $this->db->Execute("UPDATE " . TABLE_EU_WITHDRAWALS . " SET " . $set . ", updated_utc = '" . $now . "' WHERE eu_withdrawals_id = " . $id);
    }

    /**
     * A matched statement's note on its order: the staff note hidden from the
     * customer (notify_customer -1; 0 would show it on their order page, spike
     * S4), the optional status change, and the optional customer-visible line.
     * Returns the staff note's history id (0 when nothing was written).
     */
    public function noteOrder(array $s): int
    {
        $orderId = (int)$s['orders_id'];
        if ($orderId <= 0 || (int)$s['matched'] !== 1) {
            return 0;
        }
        $by = EuWithdrawalCore::text('EU_WITHDRAWAL_UPDATED_BY');
        $status = EuWithdrawalCore::settingInt('EU_WITHDRAWAL_ORDER_STATUS', 0, 0, 999999);
        $oshId = $this->history($orderId, EuWithdrawalCore::staffNote($s), $by, $status > 0 ? $status : -1, -1);
        if (EuWithdrawalCore::settingOn('EU_WITHDRAWAL_CUSTOMER_NOTE', false)) {
            $this->history($orderId, EuWithdrawalCore::customerNote($s), $by, -1, 0);
        }
        if ($oshId > 0) {
            $this->db->Execute("UPDATE " . TABLE_EU_WITHDRAWALS . " SET osh_id = " . $oshId . " WHERE eu_withdrawals_id = " . (int)$s['eu_withdrawals_id']);
        }
        return $oshId;
    }

    /**
     * Core's own history writer (every version 1.5.8 -> 3.0.0; the storefront
     * loads it in config.core.php). Its customer-email path stays off: notify
     * is -1 (hidden) or 0 (shown, no email).
     */
    protected function history(int $orderId, string $message, string $by, int $status, int $notify): int
    {
        if (!function_exists('zen_update_orders_history')) {
            return 0;
        }
        return (int)zen_update_orders_history($orderId, $message, $by, $status, $notify);
    }

    /* ----------------------------------------------------------------- *
     * Admin
     * ----------------------------------------------------------------- */

    /** Statement counts by status, every status listed. */
    public function counts(): array
    {
        $out = array_fill_keys(EuWithdrawalCore::STATUSES, 0);
        $r = EuWithdrawalCore::fresh($this->db, "SELECT status, COUNT(*) AS n FROM " . TABLE_EU_WITHDRAWALS . " GROUP BY status");
        while (!$r->EOF) {
            if (isset($out[$r->fields['status']])) {
                $out[$r->fields['status']] = (int)$r->fields['n'];
            }
            $r->MoveNext();
        }
        return $out;
    }

    /**
     * One page of statements, newest first, filtered by status and by a search
     * on name, email or the order as entered.
     *
     * @return array{rows:array, total:int, page:int, pages:int, status:string, query:string}
     */
    public function search(string $status, string $query, int $page): array
    {
        $status = in_array($status, EuWithdrawalCore::STATUSES, true) ? $status : '';
        $query = trim(substr($query, 0, 96));
        $where = [];
        if ($status !== '') {
            $where[] = "status = '" . $status . "'";
        }
        if ($query !== '') {
            $like = $this->db->prepare_input(addcslashes($query, '%_\\'));
            $where[] = "(name LIKE '%" . $like . "%' OR email LIKE '%" . $like . "%' OR order_entered LIKE '%" . $like . "%')";
        }
        $w = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int)EuWithdrawalCore::fresh($this->db, "SELECT COUNT(*) AS n FROM " . TABLE_EU_WITHDRAWALS . $w)->fields['n'];
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, $page));
        $r = EuWithdrawalCore::fresh(
            $this->db,
            "SELECT * FROM " . TABLE_EU_WITHDRAWALS . $w . " ORDER BY eu_withdrawals_id DESC LIMIT " . (($page - 1) * self::PER_PAGE) . ", " . self::PER_PAGE
        );
        $rows = [];
        while (!$r->EOF) {
            $rows[] = $r->fields;
            $r->MoveNext();
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'status' => $status, 'query' => $query];
    }

    /** Received, In Progress or Done. "Held" is left only by sending the acknowledgment. */
    public function setStatus(int $id, string $status): bool
    {
        $row = $this->find($id);
        if ($row === null || $row['status'] === 'held' || !in_array($status, ['received', 'in_progress', 'done'], true)) {
            return false;
        }
        $this->db->Execute("UPDATE " . TABLE_EU_WITHDRAWALS . " SET status = '" . $status . "', updated_utc = '" . gmdate('Y-m-d H:i:s') . "' WHERE eu_withdrawals_id = " . $id);
        return true;
    }

    public function setNote(int $id, string $note): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $this->db->Execute("UPDATE " . TABLE_EU_WITHDRAWALS . " SET admin_note = '" . $this->db->prepare_input(substr($note, 0, 4000)) . "', updated_utc = '" . gmdate('Y-m-d H:i:s') . "' WHERE eu_withdrawals_id = " . $id);
        return true;
    }

    /** A held statement staff judged genuine becomes Received (the caller then emails and notes the order). */
    public function release(int $id): ?array
    {
        $row = $this->find($id);
        if ($row === null || $row['status'] !== 'held') {
            return null;
        }
        $this->db->Execute("UPDATE " . TABLE_EU_WITHDRAWALS . " SET status = 'received', updated_utc = '" . gmdate('Y-m-d H:i:s') . "' WHERE eu_withdrawals_id = " . $id);
        return $this->find($id);
    }

    /**
     * Keep Withdrawal Records (Days): Done statements last changed longer ago
     * than that are deleted. 0 keeps them (the default: they're evidence).
     */
    public function purge(int $days, string $nowUtc): void
    {
        if ($days <= 0) {
            return;
        }
        $cutoff = gmdate('Y-m-d H:i:s', strtotime($nowUtc . ' UTC') - $days * 86400);
        $this->db->Execute("DELETE FROM " . TABLE_EU_WITHDRAWALS . " WHERE status = 'done' AND updated_utc < '" . $cutoff . "'");
    }
}
