<?php
/**
 * EU Withdrawal Button -- sending the two emails.
 *
 * Every zen_mail() call here is marked "Preview Email: <key>"; a harness
 * checks that each key has a definition in email_preview/eu_withdrawal.php,
 * which builds the email with the same compose method.
 *
 * zen_mail() returns '' on success and the error text otherwise (every
 * release 1.5.8 -> 3.0.0).
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

class EuWithdrawalMailer
{
    /**
     * The acknowledgment as it's sent: composed, then NOTIFY_EU_WITHDRAWAL_COMPOSE_ACK
     * ($s, then the subject/text/html by reference) so an add-on can add to it.
     * Preview Email builds it here too, so the preview is what goes out.
     *
     * @return array{subject:string, text:string, html:string}
     */
    public static function acknowledgment(array $s): array
    {
        $m = EuWithdrawalCore::composeAcknowledgment($s);
        EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_COMPOSE_ACK', $s, $m);
        return self::shaped($m);
    }

    /**
     * The store's notice as it's sent, through NOTIFY_EU_WITHDRAWAL_COMPOSE_NOTICE.
     *
     * @return array{subject:string, text:string, html:string}
     */
    public static function notice(array $s): array
    {
        $m = EuWithdrawalCore::composeStoreNotice($s);
        EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_COMPOSE_NOTICE', $s, $m);
        return self::shaped($m);
    }

    /** Whatever an observer did to it, the three parts are strings. */
    protected static function shaped($m): array
    {
        $m = is_array($m) ? $m : [];
        return [
            'subject' => (string)($m['subject'] ?? ''),
            'text' => (string)($m['text'] ?? ''),
            'html' => (string)($m['html'] ?? ''),
        ];
    }

    /** The customer's acknowledgment of receipt. Returns '' or the error. */
    public static function sendAcknowledgment(array $s): string
    {
        $m = self::acknowledgment($s);
        // Preview Email: eu_withdrawal_ack
        $result = zen_mail(
            (string)$s['name'],
            (string)$s['email'],
            $m['subject'],
            $m['text'],
            STORE_NAME,
            EMAIL_FROM,
            ['EMAIL_MESSAGE_HTML' => $m['html']],
            EuWithdrawalCore::MAIL_ACK
        );
        return self::outcome($result);
    }

    /**
     * zen_mail()'s answer as '' (sent) or an error. false means nothing was
     * sent at all: Send E-Mails is off, the module is in EMAIL_MODULES_TO_SKIP,
     * or a name, address or subject held a line break (every release).
     */
    public static function outcome($result): string
    {
        if ($result === false) {
            return EuWithdrawalCore::text('EU_WITHDRAWAL_MAIL_NOT_SENT');
        }
        return is_string($result) ? $result : '';
    }

    /**
     * The store's notice, to the Notify E-Mail Address setting (comma-separated
     * addresses allowed) or the store owner. Reply-to is the customer, so
     * staff can answer straight from it.
     */
    public static function sendStoreNotice(array $s): string
    {
        $m = self::notice($s);
        $errors = [];
        foreach (self::noticeRecipients() as $to) {
            // Preview Email: eu_withdrawal_notice
            $result = zen_mail(
                STORE_NAME,
                $to,
                $m['subject'],
                $m['text'],
                STORE_NAME,
                EMAIL_FROM,
                ['EMAIL_MESSAGE_HTML' => $m['html']],
                EuWithdrawalCore::MAIL_NOTICE,
                '',
                (string)$s['name'],
                (string)$s['email']
            );
            $error = self::outcome($result);
            if ($error !== '') {
                $errors[] = $to . ': ' . $error;
            }
        }
        return implode(' ', $errors);
    }

    /** The setting's addresses that look like addresses, else the store owner's. */
    public static function noticeRecipients(): array
    {
        $out = [];
        foreach (explode(',', EuWithdrawalCore::setting('EU_WITHDRAWAL_NOTIFY_EMAIL', '')) as $address) {
            $address = trim($address);
            if ($address !== '' && preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $address) === 1) {
                $out[] = $address;
            }
        }
        if ($out === [] && defined('STORE_OWNER_EMAIL_ADDRESS')) {
            $out[] = (string)STORE_OWNER_EMAIL_ADDRESS;
        }
        return $out;
    }
}
