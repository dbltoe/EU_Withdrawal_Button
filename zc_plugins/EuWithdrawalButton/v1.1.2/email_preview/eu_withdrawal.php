<?php
/**
 * EU Withdrawal Button -- the emails this plugin sends, for Preview Email.
 *
 * Preview Email (Tools > Preview Email) reads every installed plugin's
 * <version>/email_preview/*.php. Each entry builds its email with the same
 * method the plugin sends it with (add-ons' additions included) -- only the
 * data is sample data -- so what the store owner previews is the real email.
 *
 * Every zen_mail() call in this plugin is marked "Preview Email: <key>" and a
 * harness checks that each key is defined here.
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
    require_once dirname(__DIR__) . '/shared/EuWithdrawalCore.php';
}
if (!class_exists('EuWithdrawalMailer', false)) {
    require_once dirname(__DIR__) . '/shared/EuWithdrawalMailer.php';
}

if (!function_exists('eu_withdrawal_preview_statement')) {
    /**
     * A sample statement: a recent customer's name and email when Preview
     * Email has one, a sample order number, and the time as the plugin would
     * show it.
     */
    function eu_withdrawal_preview_statement(bool $matched, string $status = 'received'): array
    {
        if (function_exists('preview_email_load_plugin_language')) {
            preview_email_load_plugin_language('EuWithdrawalButton', 'catalog/includes/languages/', 'eu_withdrawal', 'extra_definitions');
        }
        $customer = function_exists('preview_email_sample_customer')
            ? preview_email_sample_customer()
            : [];
        // Preview Email 3.0.2/3.0.3 can pick One Page Checkout's guest
        // placeholder ("Guest Customer, **do not remove**"), which has no
        // email address; the acknowledgment would then show a blank one.
        if (trim((string)($customer['email'] ?? '')) === '') {
            $customer = ['name' => 'Sample Customer', 'email' => 'sample.customer@example.com'];
        }
        $now = gmdate('Y-m-d H:i:s');
        return [
            'eu_withdrawals_id' => 123,
            'name' => (string)$customer['name'],
            'email' => (string)$customer['email'],
            'order_entered' => $matched ? '#1001' : '1001',
            'orders_id' => $matched ? 1001 : 0,
            'matched' => $matched ? 1 : 0,
            'items_text' => '',
            'created_local' => EuWithdrawalCore::localTime($now, EuWithdrawalCore::timezone()),
            'status' => $status,
        ];
    }
}

return [
    [
        'key' => 'eu_withdrawal_ack',
        'group' => 'EU Withdrawal Button',
        'sort' => 10,
        'label' => 'Withdrawal Acknowledgment',
        'describe' => 'Sent to the customer as soon as they click Confirm Withdrawal: the acknowledgment of receipt Article 11a(4) requires, with their statement and its date and time. It confirms receipt only.',
        'module' => EuWithdrawalCore::MAIL_ACK,
        'page_base' => 'eu_withdrawal',
        'build' => static function (array $def): array {
            $s = eu_withdrawal_preview_statement(true);
            $m = EuWithdrawalMailer::acknowledgment($s);
            return [
                'subject' => $m['subject'],
                'text' => $m['text'],
                'block' => ['EMAIL_MESSAGE_HTML' => $m['html']],
                'to_name' => $s['name'],
                'to_email' => $s['email'],
                'notes' => ['The reference (W123) and order (#1001) are samples; the time is now, in the Store Time Zone.'],
            ];
        },
    ],
    [
        'key' => 'eu_withdrawal_notice',
        'group' => 'EU Withdrawal Button',
        'sort' => 20,
        'label' => 'Withdrawal Notice to the Store',
        'describe' => 'Sent to the Notify E-Mail Address (or the store owner) for every withdrawal, held ones included: the statement, whether it matched an order, and what to do next. Reply-to is the customer.',
        'module' => EuWithdrawalCore::MAIL_NOTICE,
        'page_base' => 'eu_withdrawal',
        'build' => static function (array $def): array {
            $s = eu_withdrawal_preview_statement(true);
            $m = EuWithdrawalMailer::notice($s);
            return [
                'subject' => $m['subject'],
                'text' => $m['text'],
                'block' => ['EMAIL_MESSAGE_HTML' => $m['html']],
                'to_name' => defined('STORE_NAME') ? STORE_NAME : 'Store',
                'to_email' => defined('STORE_OWNER_EMAIL_ADDRESS') ? STORE_OWNER_EMAIL_ADDRESS : 'owner@example.com',
                'notes' => ['Shown for a statement that matched order #1001. An unmatched or held statement says so in the subject and the text.'],
            ];
        },
    ],
];
