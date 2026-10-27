<?php
/**
 * EU Withdrawal Button -- admin page name, and the storefront page the admin
 * links to.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
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
