<?php
/**
 * EU Withdrawal Button -- the withdrawal page (Article 11a of Directive
 * 2011/83/EU, added by Directive (EU) 2023/2673).
 *
 *   index.php?main_page=eu_withdrawal               the form
 *   index.php?main_page=eu_withdrawal&order_id=29   the form, that order chosen (a logged-in customer's own)
 *   POST action=review     check the details, show them back with Confirm Withdrawal
 *   POST action=change     back to the form with the details kept
 *   POST action=confirm    save, note the order, email, then redirect to done=1
 *
 * Every POST carries `action`, so core's init_sanitize refuses it without the
 * session's securityToken (every release 1.5.8 -> 3.0.0); zen_draw_form() adds
 * the token.
 *
 * Never refuses a withdrawal: an order number and email that don't match an
 * order are saved as "not matched" for the store to sort out, and the spam trap
 * holds a statement for staff instead of throwing it away.
 *
 * Seams for an add-on (EU Withdrawal Button Pro), listed in docs/CUSTOMIZING.md:
 *   NOTIFY_EU_WITHDRAWAL_FORM_READ  review/change: the add-on reads its own fields
 *                                   into $euwExtra (kept with the pending statement);
 *                                   its 'items_lines' become part of the statement
 *   NOTIFY_EU_WITHDRAWAL_SAVED      the statement is saved, nothing sent yet
 * and the template's NOTIFY_EU_WITHDRAWAL_TPL_* points.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// Guarded: on a version upgrade the old version's files may already have
// loaded these classes from the other version folder ("Cannot redeclare class").
if (!class_exists('EuWithdrawalStore', false)) {
    require_once dirname(__DIR__, 5) . '/shared/EuWithdrawalStore.php';
}
if (!class_exists('EuWithdrawalMailer', false)) {
    require_once dirname(__DIR__, 5) . '/shared/EuWithdrawalMailer.php';
}

if (!EuWithdrawalCore::enabled()) {
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

$euwStore = new EuWithdrawalStore($db);
$euwCustomerId = EuWithdrawalStore::realCustomerId($_SESSION['customer_id'] ?? 0);
$euwLanguageCode = (string)($_SESSION['languages_code'] ?? 'en');
$euwAntiSpamField = (string)($_SESSION['antispam_fieldname'] ?? 'should_be_empty');
$euwView = 'form';
$euwErrors = [];
$euwForm = ['name' => '', 'email' => '', 'order' => '', 'orders_id' => 0, 'items' => ''];
$euwOrders = [];
$euwDone = null;
// An add-on's own data for this statement (Pro's item picker); free leaves it empty.
$euwExtra = [];

// A logged-in customer: pre-filled, and their recent orders to pick from (Recital 37).
if ($euwCustomerId > 0) {
    $euwForm = array_merge($euwForm, $euwStore->customer($euwCustomerId));
    $euwOrders = $euwStore->recentOrders($euwCustomerId, EuWithdrawalCore::settingInt('EU_WITHDRAWAL_ORDER_DAYS', 90, 1, 3650), date('Y-m-d H:i:s'));
    $euwPick = (int)($_GET['order_id'] ?? 0);
    if (isset($euwOrders[$euwPick])) {
        $euwForm['orders_id'] = $euwPick;
    }
}

$euwAction = (string)($_POST['action'] ?? '');

if ($euwAction === 'review' || $euwAction === 'change') {
    // Core's zen_db_prepare_input(), as on every storefront form: it closes up
    // runs of spaces and turns < and > into _. Every output escapes what's left.
    $euwForm['name'] = EuWithdrawalCore::cut(trim(zen_db_prepare_input((string)($_POST['euw_name'] ?? ''))), 128);
    $euwForm['email'] = EuWithdrawalCore::cut(trim(zen_db_prepare_input((string)($_POST['euw_email'] ?? ''))), 96);
    $euwForm['order'] = EuWithdrawalCore::cut(trim(zen_db_prepare_input((string)($_POST['euw_order'] ?? ''))), 64);
    $euwForm['items'] = EuWithdrawalCore::cut(trim(zen_db_prepare_input((string)($_POST['euw_items'] ?? ''))), 2000);
    $euwPick = (int)($_POST['euw_orders_id'] ?? 0);
    $euwForm['orders_id'] = isset($euwOrders[$euwPick]) ? $euwPick : 0;

    EuWithdrawalCore::notify(
        'NOTIFY_EU_WITHDRAWAL_FORM_READ',
        ['action' => $euwAction, 'form' => $euwForm, 'orders' => $euwOrders, 'customers_id' => $euwCustomerId],
        $euwExtra,
        $euwErrors
    );
    $euwExtra = is_array($euwExtra) ? $euwExtra : [];
    $euwErrors = is_array($euwErrors) ? array_values(array_filter($euwErrors, 'is_string')) : [];

    if ($euwAction === 'review') {
        if ($euwForm['name'] === '') {
            $euwErrors[] = EuWithdrawalCore::text('EU_WITHDRAWAL_ERR_NAME');
        }
        $euwValidEmail = function_exists('zen_validate_email') ? (bool)zen_validate_email($euwForm['email']) : filter_var($euwForm['email'], FILTER_VALIDATE_EMAIL) !== false;
        if (!$euwValidEmail) {
            $euwErrors[] = EuWithdrawalCore::text('EU_WITHDRAWAL_ERR_EMAIL');
        }
        if ($euwForm['orders_id'] === 0 && $euwForm['order'] === '') {
            $euwErrors[] = EuWithdrawalCore::text('EU_WITHDRAWAL_ERR_ORDER');
        }
        if ($euwErrors === []) {
            $euwView = 'review';
            $_SESSION['eu_withdrawal_pending'] = $euwForm + [
                // The spam trap: core's per-session field name, or the classic one.
                'held' => (trim((string)($_POST[$euwAntiSpamField] ?? '')) !== '' || trim((string)($_POST['should_be_empty'] ?? '')) !== '') ? 1 : 0,
                'extra' => $euwExtra,
            ];
        }
    }
}

if ($euwAction === 'confirm') {
    $euwPending = $_SESSION['eu_withdrawal_pending'] ?? null;
    unset($_SESSION['eu_withdrawal_pending']);
    if (!is_array($euwPending)) {
        $euwErrors[] = EuWithdrawalCore::text('EU_WITHDRAWAL_ERR_EXPIRED');
    } else {
        $euwExtra = is_array($euwPending['extra'] ?? null) ? $euwPending['extra'] : [];
        $euwOrderId = (int)$euwPending['orders_id'] > 0
            ? (int)$euwPending['orders_id']
            : $euwStore->matchOrder((string)$euwPending['order'], (string)$euwPending['email'], $euwCustomerId);
        $euwCountry = $euwStore->visitorCountry($euwCustomerId, '', 0, $_SERVER, EuWithdrawalCore::setting('EU_WITHDRAWAL_COUNTRY_VARIABLE', 'HTTP_CF_IPCOUNTRY'));
        $euwNow = gmdate('Y-m-d H:i:s');
        $euwZone = EuWithdrawalCore::timezone();
        $euwSaved = $euwStore->save([
            'created_utc' => $euwNow,
            'created_local' => EuWithdrawalCore::localTime($euwNow, $euwZone),
            'timezone' => $euwZone,
            'customers_id' => $euwCustomerId,
            'name' => (string)$euwPending['name'],
            'email' => (string)$euwPending['email'],
            'order_entered' => (string)$euwPending['order'] !== '' ? (string)$euwPending['order'] : '#' . (int)$euwPending['orders_id'],
            'orders_id' => $euwOrderId,
            'items_text' => EuWithdrawalCore::combineItems($euwExtra['items_lines'] ?? [], (string)$euwPending['items']),
            'language' => (string)($_SESSION['language'] ?? ''),
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'country' => $euwCountry[0],
            'held' => (int)$euwPending['held'],
        ]);
        if ($euwSaved === null) {
            // Kept for another try; nothing was sent.
            $_SESSION['eu_withdrawal_pending'] = $euwPending;
            $euwErrors[] = sprintf(EuWithdrawalCore::text('EU_WITHDRAWAL_ERR_SAVE'), defined('STORE_OWNER_EMAIL_ADDRESS') ? STORE_OWNER_EMAIL_ADDRESS : '');
            $euwForm = array_merge($euwForm, array_intersect_key($euwPending, $euwForm));
        } else {
            $euwId = (int)$euwSaved['eu_withdrawals_id'];
            EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_SAVED', ['withdrawal' => $euwSaved, 'extra' => $euwExtra]);
            if ($euwSaved['status'] !== 'held') {
                $euwStore->noteOrder($euwSaved);
                $euwStore->markAcknowledged($euwId, EuWithdrawalMailer::sendAcknowledgment($euwSaved));
            }
            EuWithdrawalMailer::sendStoreNotice($euwSaved);
            $_SESSION['eu_withdrawal_done'] = $euwId;
            zen_redirect(zen_href_link(FILENAME_EU_WITHDRAWAL, 'done=1', 'SSL'));
        }
    }
}

if (isset($_GET['done']) && !empty($_SESSION['eu_withdrawal_done'])) {
    $euwDone = $euwStore->find((int)$_SESSION['eu_withdrawal_done']);
    if ($euwDone !== null) {
        $euwView = 'done';
    }
}

$euwFormAction = zen_href_link(FILENAME_EU_WITHDRAWAL, '', 'SSL');
$euwConfirmLabel = EuWithdrawalCore::label('confirm', $euwLanguageCode);

// A personal page: never indexed. Core's meta_tags.php builds the page title
// from NAVBAR_TITLE when a page defines it.
header('X-Robots-Tag: noindex, nofollow');
if (!defined('NAVBAR_TITLE')) {
    define('NAVBAR_TITLE', EuWithdrawalCore::text('EU_WITHDRAWAL_PAGE_TITLE'));
}
$breadcrumb->add(EuWithdrawalCore::text('EU_WITHDRAWAL_PAGE_TITLE'));
