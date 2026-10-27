<?php
/**
 * EU Withdrawal Button -- admin observer.
 *
 * Zen Cart finds this file itself (init_observers.php scans every installed
 * plugin's classes/observers/ for auto.*.php). It must NOT also be listed in an
 * auto_loader; a second include fatals with "Cannot redeclare class".
 *
 * Only the email format hook, for an acknowledgment sent from the admin (Send
 * Acknowledgment, or Preview Email's Send Test): HTML for an address that
 * isn't a customer.
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
    require_once dirname(__DIR__, 4) . '/shared/EuWithdrawalCore.php';
}

class zcObserverEuWithdrawalAdmin extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT']);
    }

    public function update(&$class, $eventID, $p1 = null, &$p2 = null, &$p3 = null)
    {
        if ($eventID === 'NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT') {
            EuWithdrawalCore::mailFormat($p2, $p3);
        }
    }
}
