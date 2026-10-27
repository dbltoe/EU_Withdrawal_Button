<?php
/**
 * EU Withdrawal Button -- storefront page name.
 *
 * In extra_datafiles (loaded per plugin from v1.5.8 on) rather than a root
 * filenames.php, which only v2.2.0+ loads. Guarded, because v2.2.0+ may also
 * pick up a root file.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!defined('FILENAME_EU_WITHDRAWAL')) {
    define('FILENAME_EU_WITHDRAWAL', 'eu_withdrawal');
}
