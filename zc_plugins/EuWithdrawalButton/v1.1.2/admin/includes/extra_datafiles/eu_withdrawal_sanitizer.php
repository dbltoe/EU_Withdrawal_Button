<?php
/**
 * EU Withdrawal Button -- tells core's admin request sanitizer about the two
 * free-text fields on Customers > Withdrawals.
 *
 * Without this, strict sanitizing runs htmlspecialchars() on them, so a staff
 * note "O'Brien <b>" was saved as "O&#039;Brien &lt;b&gt;" and a search for
 * O'Brien found nothing. The page escapes both on output and in SQL itself.
 *
 * Plugin admin datafiles load after application_bootstrap.php requires the
 * sanitizer class and before init_sanitize.php runs it (1.5.8 through 3.0.0).
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (class_exists('AdminRequestSanitizer', false)) {
    AdminRequestSanitizer::getInstance()->addComplexSanitization([
        // The same rule core gives order-history comments.
        'admin_note' => ['sanitizerType' => 'PRODUCT_DESC_REGEX', 'method' => 'post', 'pages' => ['eu_withdrawals']],
        'q' => ['sanitizerType' => 'WORDS_AND_SYMBOLS_REGEX', 'method' => 'get', 'pages' => ['eu_withdrawals']],
    ]);
}
