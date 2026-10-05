<?php
/**
 * EU Withdrawal Button -- storefront observer.
 *
 * Zen Cart finds this file itself (init_observers.php scans every installed
 * plugin's classes/observers/ for auto.*.php). It must NOT also be listed in an
 * auto_loader; a second include fatals with "Cannot redeclare class".
 *
 * The footer button, without template edits (spike S1):
 *   NOTIFY_FOOTER_AFTER_NAVSUPP  inside the footer, under its links: responsive_classic
 *                                and template_default from 2.1.0, ZCA Bootstrap 3.8.0
 *   NOTIFY_FOOTER_END            just before </body>: every version and template, and pages
 *                                that turn the footer off. Used only when the first didn't
 *                                fire on this request; its copy moves itself up under the
 *                                footer links when the page has them.
 *
 * Who sees it: Show Withdrawal Button To (spike S7). Only the button is
 * hidden; the withdrawal page itself always answers.
 *
 * And NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT: a guest's acknowledgment goes as
 * HTML (core sends a non-customer text-only).
 *
 * And NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND (order.php, same arguments
 * 1.5.8 -> 3.0.0): the order confirmation email gets the withdrawal link
 * (Withdrawal Link in Order Email?), by the order's country under the same
 * rule as the button. Text part before core's disclaimer, HTML part in the
 * template's $EMAIL_ORDER_MESSAGE slot.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// Guarded: on a version upgrade the old version's files may already have
// loaded this class from the other version folder ("Cannot redeclare class").
if (!class_exists('EuWithdrawalStore', false)) {
    require_once dirname(__DIR__, 4) . '/shared/EuWithdrawalStore.php';
}

class zcObserverEuWithdrawal extends base
{
    /** @var bool the button is out on this request */
    protected $shown = false;

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_FOOTER_AFTER_NAVSUPP',
            'NOTIFY_FOOTER_END',
            'NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT',
            'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND',
            'NOTIFY_EU_WITHDRAWAL_BUTTON',
        ]);
    }

    public function update(&$class, $eventID, $p1 = null, &$p2 = null, &$p3 = null)
    {
        if ($eventID === 'NOTIFY_FOOTER_AFTER_NAVSUPP') {
            $this->render('footer');
        } elseif ($eventID === 'NOTIFY_FOOTER_END') {
            $this->render('end');
        } elseif ($eventID === 'NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT') {
            EuWithdrawalCore::mailFormat($p2, $p3);
        } elseif ($eventID === 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND') {
            $this->addOrderEmailLink($class, is_array($p1) ? (int)($p1['zf_insert_id'] ?? 0) : 0, $p2, $p3);
        } elseif ($eventID === 'NOTIFY_EU_WITHDRAWAL_BUTTON') {
            $where = $p1['label'] ?? 'footer';
            $this->render($where);
            $this->shown = $p1['shown'] ?? true;
        }
    }

    /**
     * The withdrawal link in the order confirmation email.
     *
     * @param mixed $order    the order object
     * @param mixed $text     the text email, by reference
     * @param mixed $htmlMsg  the HTML template's fields, by reference
     */
    public function addOrderEmailLink($order, int $orderId, &$text, &$htmlMsg): void
    {
        if (!EuWithdrawalCore::enabled() || !EuWithdrawalCore::settingOn('EU_WITHDRAWAL_ORDER_EMAIL_LINK', true)
            || !defined('FILENAME_EU_WITHDRAWAL') || $orderId <= 0 || !is_string($text)) {
            return;
        }
        $mode = EuWithdrawalCore::setting('EU_WITHDRAWAL_SHOW_TO', EuWithdrawalCore::SHOW_ALL);
        $countries = EuWithdrawalCore::parseCountries(EuWithdrawalCore::setting('EU_WITHDRAWAL_COUNTRIES', EuWithdrawalCore::COUNTRIES_DEFAULT));
        $iso = static function ($address): string {
            return is_array($address) && is_array($address['country'] ?? null) ? (string)($address['country']['iso_code_2'] ?? '') : '';
        };
        if (!EuWithdrawalCore::orderEmailShows($mode, $countries, $iso($order->delivery ?? null), $iso($order->billing ?? null))) {
            return;
        }
        $link = EuWithdrawalCore::orderEmailLink(
            zen_href_link(FILENAME_EU_WITHDRAWAL, 'order_id=' . $orderId, 'SSL', false),
            EuWithdrawalCore::label('link', (string)($_SESSION['languages_code'] ?? 'en'))
        );
        $text = EuWithdrawalCore::insertBeforeFooter($text, $link['text']);
        if (is_array($htmlMsg)) {
            $htmlMsg['EMAIL_ORDER_MESSAGE'] = (string)($htmlMsg['EMAIL_ORDER_MESSAGE'] ?? '') . $link['html'];
        }
    }

    protected function render(string $where): void
    {
        if ($this->shown) {
            return;
        }
        $this->shown = true;
        echo $this->buttonFor($where);
    }

    /** The button's HTML for this request, or '' when it shouldn't show. */
    public function buttonFor(string $where): string
    {
        global $db, $current_page_base;
        if (!EuWithdrawalCore::enabled() || !defined('FILENAME_EU_WITHDRAWAL')) {
            return '';
        }
        $page = (string)($current_page_base ?? '');
        if ($page === FILENAME_EU_WITHDRAWAL) {
            return '';
        }
        $customerId = EuWithdrawalStore::realCustomerId($_SESSION['customer_id'] ?? 0);
        $orderId = $page === 'account_history_info' ? (int)($_GET['order_id'] ?? 0) : 0;

        // The country lookups run only when the store filters by country.
        $mode = EuWithdrawalCore::setting('EU_WITHDRAWAL_SHOW_TO', EuWithdrawalCore::SHOW_ALL);
        if ($mode === EuWithdrawalCore::SHOW_COUNTRIES && isset($db) && is_object($db)) {
            $store = new EuWithdrawalStore($db);
            $country = $store->visitorCountry($customerId, $page, $orderId, $_SERVER, EuWithdrawalCore::setting('EU_WITHDRAWAL_COUNTRY_VARIABLE', 'HTTP_CF_IPCOUNTRY'));
            $countries = EuWithdrawalCore::parseCountries(EuWithdrawalCore::setting('EU_WITHDRAWAL_COUNTRIES', EuWithdrawalCore::COUNTRIES_DEFAULT));
            if (!EuWithdrawalCore::buttonShows($mode, $countries, $country[0])) {
                return '';
            }
        }

        // On an order's page, the button opens the form with that order chosen.
        $href = zen_href_link(FILENAME_EU_WITHDRAWAL, $customerId > 0 && $orderId > 0 ? 'order_id=' . $orderId : '', 'SSL');
        return EuWithdrawalCore::buttonHtml($href, EuWithdrawalCore::label('link', (string)($_SESSION['languages_code'] ?? 'en')), $where);
    }
}
