<?php
/**
 * EU Withdrawal Button -- admin page name, and the storefront page the admin
 * links to.
 *
 * @package  EuWithdrawalButton
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!defined('FILENAME_EU_WITHDRAWALS')) {
    define('FILENAME_EU_WITHDRAWALS', 'eu_withdrawals');
}
if (!defined('FILENAME_EU_WITHDRAWAL')) {
    define('FILENAME_EU_WITHDRAWAL', 'eu_withdrawal');
}
