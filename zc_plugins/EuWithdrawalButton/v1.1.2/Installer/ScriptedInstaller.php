<?php
/**
 * EU Withdrawal Button -- Plugin Manager installer.
 *
 * Limited to the API every supported release has (v1.5.8 -> v3.0.0):
 *
 *   - Zen Cart calls only executeInstall(), executeUninstall() and
 *     executeUpgrade(). Any other method here is a helper, never a hook.
 *   - Returning false does not refuse an install; an errorContainer entry
 *     does. All checks run before anything is written, because nothing rolls
 *     back.
 *   - executeUpgrade() takes an optional argument: v1.5.8 passes none.
 *   - Database work goes through executeInstallerSql() or the queryFactory;
 *     the configuration helpers added in v2.0.1/v2.1.0 don't exist on v1.5.8.
 *
 * Every step is idempotent, so an upgrade is a re-run of the install.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

// Guarded: on a version upgrade the old version's files may already have
// loaded this class from the other version folder ("Cannot redeclare class").
if (!class_exists('EuWithdrawalCore', false)) {
    require_once dirname(__DIR__) . '/shared/EuWithdrawalCore.php';
}

class ScriptedInstaller extends ScriptedInstallBase
{
    public const CONFIG_GROUP_TITLE = 'EU Withdrawal Button';

    /** plugin_control.unique_key: the zc_plugins folder name. */
    public const PLUGIN_KEY = 'EuWithdrawalButton';

    public const ADMIN_PAGE_KEYS = ['configEuWithdrawalButton', 'customersEuWithdrawals'];

    /**
     * The plugin's table, by TABLE_* constant (it carries DB_PREFIX), with the
     * columns that prove an existing table is ours and not another plugin's.
     */
    public const OWNERSHIP = [
        'TABLE_EU_WITHDRAWALS' => ['order_entered', 'created_local', 'ack_sent_utc'],
    ];

    protected function executeInstall()
    {
        EuWithdrawalCore::defineTables();

        $clash = $this->euwForeignTable();
        if ($clash !== '') {
            $this->errorContainer->addError(
                0,
                'EU Withdrawal Button was not installed: the database already has a table named "' . $clash
                . '" that belongs to something else. Rename or remove that table, then install again.',
                true
            );
            return false;
        }

        $groupId = $this->euwGetOrCreateConfigGroup();
        if ($groupId === 0) {
            return false;
        }
        if ($this->euwAddConfigurationKeys($groupId) === false) {
            return false;
        }
        $this->euwRegisterAdminPages($groupId);
        if ($this->euwCreateTables() === false) {
            return false;
        }
        // Last, so the Settings button finds the page registered above.
        $this->euwRefreshPluginDescription();

        $this->euwLog('EU Withdrawal Button: installed/upgraded.');
        return true;
    }

    protected function executeUpgrade($oldVersion = null)
    {
        return $this->executeInstall();
    }

    protected function executeUninstall()
    {
        EuWithdrawalCore::defineTables();
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);

        // The statements are evidence of when a customer withdrew: kept unless
        // the owner asked for them to go.
        $deleteData = $this->euwExistingValue('EU_WITHDRAWAL_DELETE_ON_UNINSTALL') === 'true';
        if ($deleteData) {
            foreach (array_keys(self::OWNERSHIP) as $table) {
                $this->executeInstallerSql("DROP TABLE IF EXISTS " . constant($table));
            }
        }

        $groupId = $this->euwGetConfigGroupId();
        if ($groupId > 0) {
            $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_group_id = " . $groupId);
            $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . $groupId);
        }
        // Strays outside the group, by name: only this plugin's own keys.
        $ours = array_map(static function ($key) {
            return $key['key'];
        }, $this->euwConfigurationKeys());
        $this->executeInstallerSql("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $ours) . "')");
        // After the admin pages are gone, so the saved panel has no Settings button.
        $this->euwRefreshPluginDescription();

        $this->euwLog('EU Withdrawal Button: uninstalled' . ($deleteData ? ', withdrawal records deleted.' : ', withdrawal records kept.'));
        return true;
    }

    /* ----------------------------------------------------------------- *
     * Checks
     * ----------------------------------------------------------------- */

    /** The first table that exists but isn't shaped like ours, or ''. */
    protected function euwForeignTable(): string
    {
        foreach (self::OWNERSHIP as $table => $markers) {
            $full = constant($table);
            $r = EuWithdrawalCore::fresh($this->dbConn, "SHOW TABLES LIKE '" . $this->dbConn->prepare_input($full) . "'");
            if ($r->EOF) {
                continue;
            }
            $columns = [];
            $c = EuWithdrawalCore::fresh($this->dbConn, "SHOW COLUMNS FROM " . $full);
            while (!$c->EOF) {
                $columns[] = $c->fields['Field'];
                $c->MoveNext();
            }
            if (array_diff($markers, $columns) !== []) {
                return $full;
            }
        }
        return '';
    }

    /* ----------------------------------------------------------------- *
     * Tables
     * ----------------------------------------------------------------- */

    protected function euwCreateTables(): bool
    {
        $never = "'" . EuWithdrawalCore::NEVER . "'";
        $sql = [
            "CREATE TABLE IF NOT EXISTS " . TABLE_EU_WITHDRAWALS . " (
                eu_withdrawals_id int(11) unsigned NOT NULL AUTO_INCREMENT,
                created_utc datetime NOT NULL DEFAULT $never,
                created_local varchar(64) NOT NULL DEFAULT '',
                timezone varchar(64) NOT NULL DEFAULT '',
                customers_id int(11) NOT NULL DEFAULT 0,
                name varchar(128) NOT NULL DEFAULT '',
                email varchar(96) NOT NULL DEFAULT '',
                order_entered varchar(64) NOT NULL DEFAULT '',
                orders_id int(11) NOT NULL DEFAULT 0,
                matched tinyint(1) NOT NULL DEFAULT 0,
                items_text text,
                language varchar(32) NOT NULL DEFAULT '',
                ip varchar(45) NOT NULL DEFAULT '',
                country char(2) NOT NULL DEFAULT '',
                status varchar(16) NOT NULL DEFAULT 'received',
                ack_sent_utc datetime NOT NULL DEFAULT $never,
                ack_error varchar(255) NOT NULL DEFAULT '',
                osh_id int(11) NOT NULL DEFAULT 0,
                admin_note text,
                updated_utc datetime NOT NULL DEFAULT $never,
                PRIMARY KEY (eu_withdrawals_id),
                KEY idx_euw_status (status),
                KEY idx_euw_orders (orders_id),
                KEY idx_euw_email (email)
            )",
        ];
        foreach ($sql as $statement) {
            if ($this->executeInstallerSql($statement) === false) {
                return false;
            }
        }
        return true;
    }

    /* ----------------------------------------------------------------- *
     * Configuration
     * ----------------------------------------------------------------- */

    protected function euwConfigurationKeys(): array
    {
        $yesNo = "zen_cfg_select_option(array('true', 'false'), ";
        $showTo = "zen_cfg_select_option(array('" . EuWithdrawalCore::SHOW_ALL . "', '" . EuWithdrawalCore::SHOW_COUNTRIES . "'), ";

        return [
            [
                'key' => 'EU_WITHDRAWAL_STATUS',
                'title' => 'Enable the Withdrawal Button?',
                'value' => 'true',
                'description' => 'When true, the "Withdraw From Contract Here" button shows in the footer and the withdrawal page takes statements. Article 11a of the EU Consumer Rights Directive requires this from 19 June 2026 for stores selling to consumers in the EU through a website.',
                'sort_order' => 10,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'EU_WITHDRAWAL_SHOW_TO',
                'title' => 'Show Withdrawal Button To',
                'value' => EuWithdrawalCore::SHOW_ALL,
                'description' => 'All Visitors (the safe choice: no EU customer can miss it), or only visitors whose country is in Selected Countries.<br><br>The country is, in order: the order\'s country on a customer\'s order page, a logged-in customer\'s default address, then the Country Server Variable. When it can\'t be told, the button shows. Only the button is hidden; the withdrawal page always works for anyone.<br><br>IP country is sometimes wrong (a traveler, a VPN). Whether customers outside the EU have a right of withdrawal depends on your terms; ask your lawyer.',
                'sort_order' => 20,
                'set_function' => $showTo,
            ],
            [
                'key' => 'EU_WITHDRAWAL_COUNTRIES',
                'title' => 'Selected Countries',
                'value' => EuWithdrawalCore::COUNTRIES_DEFAULT,
                'description' => 'Two-letter country codes, separated by commas, that see the button when Show Withdrawal Button To is Selected Countries. The default is the 27 EU countries plus Iceland, Liechtenstein and Norway, which apply the same directive. The UK doesn\'t.',
                'sort_order' => 30,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_COUNTRY_VARIABLE',
                'title' => 'Country Server Variable',
                'value' => 'HTTP_CF_IPCOUNTRY',
                'description' => 'The server variable holding a guest\'s two-letter country code. HTTP_CF_IPCOUNTRY is Cloudflare\'s; a host\'s GeoIP module may use GEOIP_COUNTRY_CODE or MM_COUNTRY_CODE. Without Cloudflare or a GeoIP module there is none, guests\' country is unknown, and they always see the button. The plugin never looks a country up anywhere.',
                'sort_order' => 40,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_AUDIENCE_NOTE',
                'title' => 'Who the Page Is For (Optional)',
                'value' => '',
                'description' => 'A line shown above the withdrawal form, for example: For customers in the EU, Iceland, Liechtenstein and Norway. Blank for none.',
                'sort_order' => 50,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_ORDER_EMAIL_LINK',
                'title' => 'Withdrawal Link in Order E-Mail?',
                'value' => 'true',
                'description' => 'When true, the order confirmation email carries a "Withdraw From Contract Here" link to the withdrawal page, above the store\'s disclaimer. It follows Show Withdrawal Button To, by the order\'s delivery country (else billing).',
                'sort_order' => 55,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'EU_WITHDRAWAL_TIMEZONE',
                'title' => 'Store Time Zone',
                'value' => '',
                'description' => 'The time zone for the date and time of submission in the acknowledgment, for example Europe/Madrid or America/Chicago. Blank uses PHP\'s own (often UTC on a hosting server).',
                'sort_order' => 60,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_NOTIFY_EMAIL',
                'title' => 'Notify E-Mail Address',
                'value' => '',
                'description' => 'Where the store\'s notice of each withdrawal goes. Several addresses separated by commas. Blank uses the store owner\'s address.',
                'sort_order' => 70,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_ORDER_DAYS',
                'title' => 'Orders to List (Days)',
                'value' => '90',
                'description' => 'A logged-in customer picks from their orders placed in the last this-many days, or types another order number. 1 to 3650.',
                'sort_order' => 80,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_ORDER_STATUS',
                'title' => 'Order Status on Withdrawal',
                'value' => '0',
                'description' => 'When a withdrawal matches an order, the order moves to this status (no email to the customer). 0 leaves the status alone.',
                'sort_order' => 90,
                'set_function' => 'zen_cfg_pull_down_order_statuses(',
                'use_function' => 'zen_get_order_status_name',
            ],
            [
                'key' => 'EU_WITHDRAWAL_CUSTOMER_NOTE',
                'title' => 'Show the Customer a Note on Their Order?',
                'value' => 'false',
                'description' => 'When true, a matched order\'s history also gets "Withdrawal received on (date and time)." where the customer can see it on their order page. The staff note is always hidden from the customer.',
                'sort_order' => 100,
                'set_function' => $yesNo,
            ],
            [
                'key' => 'EU_WITHDRAWAL_RETENTION_DAYS',
                'title' => 'Keep Done Withdrawals (Days)',
                'value' => '0',
                'description' => 'Withdrawals marked Done are deleted this many days after they were last changed. 0 keeps them: they record when a customer withdrew, which you may need as evidence.',
                'sort_order' => 110,
                'set_function' => '',
            ],
            [
                'key' => 'EU_WITHDRAWAL_DELETE_ON_UNINSTALL',
                'title' => 'Delete Withdrawal Records on Uninstall?',
                'value' => 'false',
                'description' => 'When false, uninstalling keeps every withdrawal statement, so a re-install picks them up again.',
                'sort_order' => 120,
                'set_function' => $yesNo,
            ],
        ];
    }

    protected function euwAddConfigurationKeys(int $groupId)
    {
        $db = $this->dbConn;
        foreach ($this->euwConfigurationKeys() as $key) {
            $setFunction = empty($key['set_function']) ? 'NULL' : "'" . $db->prepare_input($key['set_function']) . "'";
            $useFunction = empty($key['use_function']) ? 'NULL' : "'" . $db->prepare_input($key['use_function']) . "'";
            $ok = $this->executeInstallerSql(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description,
                     configuration_group_id, sort_order, date_added, use_function, set_function)
                 VALUES
                    ('" . $db->prepare_input($key['title']) . "', '" . $db->prepare_input($key['key']) . "',
                     '" . $db->prepare_input($key['value']) . "', '" . $db->prepare_input($key['description']) . "',
                     " . $groupId . ", " . (int)$key['sort_order'] . ", now(), " . $useFunction . ", " . $setFunction . ")"
            );
            if ($ok === false) {
                return false;
            }
            // The value belongs to the owner and survives an upgrade; the
            // wording, order and input type belong to the plugin.
            $ok = $this->executeInstallerSql(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_title = '" . $db->prepare_input($key['title']) . "',
                        configuration_description = '" . $db->prepare_input($key['description']) . "',
                        configuration_group_id = " . $groupId . ",
                        sort_order = " . (int)$key['sort_order'] . ",
                        use_function = " . $useFunction . ",
                        set_function = " . $setFunction . "
                  WHERE configuration_key = '" . $db->prepare_input($key['key']) . "'
                  LIMIT 1"
            );
            if ($ok === false) {
                return false;
            }
        }
        return true;
    }

    protected function euwExistingValue(string $key): string
    {
        $r = EuWithdrawalCore::fresh(
            $this->dbConn,
            "SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $this->dbConn->prepare_input($key) . "' LIMIT 1"
        );
        return $r->EOF ? '' : (string)$r->fields['configuration_value'];
    }

    protected function euwGetConfigGroupId(): int
    {
        $r = EuWithdrawalCore::fresh(
            $this->dbConn,
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP
            . " WHERE configuration_group_title = '" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "' LIMIT 1"
        );
        return $r->EOF ? 0 : (int)$r->fields['configuration_group_id'];
    }

    protected function euwGetOrCreateConfigGroup(): int
    {
        $id = $this->euwGetConfigGroupId();
        if ($id > 0) {
            return $id;
        }
        $ok = $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES ('" . $this->dbConn->prepare_input(self::CONFIG_GROUP_TITLE) . "',
                     'The EU withdrawal function (Article 11a): the footer button, the withdrawal page and its emails.', 0, 1)"
        );
        if ($ok === false) {
            return 0;
        }
        $id = $this->euwGetConfigGroupId();
        if ($id > 0) {
            $this->executeInstallerSql(
                "UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = " . $id . " WHERE configuration_group_id = " . $id . " LIMIT 1"
            );
        }
        return $id;
    }

    /** Without an admin_pages row the group never shows under Configuration. */
    protected function euwRegisterAdminPages(int $groupId): void
    {
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);
        zen_register_admin_page(
            'configEuWithdrawalButton',
            'BOX_CONFIGURATION_EU_WITHDRAWAL_BUTTON',
            'FILENAME_CONFIGURATION',
            'gID=' . $groupId,
            'configuration',
            'Y',
            $groupId
        );
        zen_register_admin_page(
            'customersEuWithdrawals',
            'BOX_CUSTOMERS_EU_WITHDRAWALS',
            'FILENAME_EU_WITHDRAWALS',
            '',
            'customers',
            'Y',
            56
        );
    }

    /**
     * Save the manifest's description again, now that the Settings page has
     * been registered (install, upgrade) or removed (uninstall).
     *
     * Needed on v1.5.8, v2.0 and v2.1, which store plugin_control.description
     * only when Plugin Manager first sees the plugin, before Install, so the
     * Settings button (built in manifest.php from the registered admin page)
     * would never appear there. v2.2 and later rewrite the description from
     * the manifest on every Plugin Manager visit, so this is a no-op there in
     * effect. Core's own install/uninstall step changes only status and
     * version, so this description stays.
     *
     * @return bool
     */
    protected function euwRefreshPluginDescription()
    {
        if (!defined('TABLE_PLUGIN_CONTROL')) {
            return true;
        }
        $manifest = require dirname(__DIR__) . '/manifest.php';
        if (!is_array($manifest) || !isset($manifest['pluginDescription'])) {
            return true;
        }

        return $this->executeInstallerSql(
            "UPDATE " . TABLE_PLUGIN_CONTROL . "
                SET description = '" . $this->dbConn->prepare_input($manifest['pluginDescription']) . "'
              WHERE unique_key = '" . $this->dbConn->prepare_input(self::PLUGIN_KEY) . "'
              LIMIT 1"
        );
    }

    protected function euwLog(string $message): void
    {
        if (function_exists('zen_record_admin_activity')) {
            zen_record_admin_activity($message, 'info');
        }
    }
}
