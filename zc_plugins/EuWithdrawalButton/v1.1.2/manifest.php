<?php
/**
 * EU Withdrawal Button -- plugin manifest.
 *
 * pluginDescription is echoed unescaped into Plugin Manager's info box on every
 * release from v1.5.8 to v3.0.0, so the ReadMe link lives here. On v1.5.8,
 * v2.0 and v2.1 the description and pluginId are written only when Plugin
 * Manager first sees the plugin, and after that only this plugin's installer
 * refreshes the description (at Install, Upgrade and Uninstall), so both have
 * to be right before the first store installs it.
 *
 * One row of buttons: ReadMe | Settings | Forum Thread | GitHub (every free
 * dbltoe plugin has the thread button). Settings shows only once the plugin is
 * installed. The GitHub and forum buttons render nothing while their URL is
 * empty: a link that 404s is worse than none.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

$euwPluginDir = 'zc_plugins/EuWithdrawalButton/v1.1.2/';
$euwReadmeUrl = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . $euwPluginDir . 'readme.html';
$euwGithubUrl = 'https://github.com/dbltoe/EU_Withdrawal_Button';
$euwForumUrl = 'https://www.zen-cart.com/threads/207395';

/**
 * The Settings button: this plugin's Configuration page, shown only once the
 * plugin is installed. Its gID is assigned at install, so it differs from
 * store to store and can't be written here; it's read from the admin page the
 * installer registers (page key configEuWithdrawalButton). The link is
 * relative ("index.php?cmd=configuration&gID=N") because Plugin Manager is an
 * admin page, so it survives a domain or admin-folder rename.
 *
 * Zen Cart reads manifests only on the Plugin Manager page, but the lookup is
 * still guarded so a manifest read anywhere else (no database, catalog side,
 * CLI) just leaves the button out. On v2.2 and later this file is re-read on
 * every Plugin Manager visit, so the button follows the install state by
 * itself; on v1.5.8, v2.0 and v2.1 the installer saves the re-read
 * description at Install, Upgrade and Uninstall
 * (euwRefreshPluginDescription).
 */
$euwSettingsUrl = '';
if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG === true && defined('TABLE_ADMIN_PAGES')
    && isset($GLOBALS['db']) && is_object($GLOBALS['db'])) {
    $euwPage = $GLOBALS['db']->Execute(
        "SELECT main_page, page_params FROM " . TABLE_ADMIN_PAGES
        . " WHERE page_key = 'configEuWithdrawalButton' LIMIT 1"
    );
    if (!$euwPage->EOF && defined($euwPage->fields['main_page'])) {
        $euwSettingsUrl = 'index.php?cmd=' . constant($euwPage->fields['main_page'])
            . '&' . $euwPage->fields['page_params'];
    }
}

/**
 * Styled like Plugin Manager's own Install / Uninstall buttons. Spacing is
 * inline, which beats any theme `.btn` margin: every gap is 4px (the wrapper
 * supplies the space before the first button, each button the space after
 * itself) and the side padding is 8px, so all four fit on one line from a
 * 1366px-wide window up and the Author line stays where it was. Settings
 * opens in the same tab (it's the admin itself); the others open a new one.
 */
$euwGap = '4px';
$euwButtonStyle = ' style="margin:0 ' . $euwGap . ' 0 0;padding-left:8px;padding-right:8px"';
$euwButton = static function ($url, $label, $newTab) use ($euwButtonStyle) {
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
        . ($newTab ? ' target="_blank" rel="noopener noreferrer"' : '')
        . ' class="btn btn-primary" role="button"' . $euwButtonStyle . '>' . $label . '</a>';
};
$euwLinks = '<div style="margin:10px 0 0;padding:0 0 0 ' . $euwGap . '">'
    . $euwButton($euwReadmeUrl, 'ReadMe', true)
    . ($euwSettingsUrl !== '' ? $euwButton($euwSettingsUrl, 'Settings', false) : '')
    . ($euwForumUrl !== '' ? $euwButton($euwForumUrl, 'Forum Thread', true) : '')
    . ($euwGithubUrl !== '' ? $euwButton($euwGithubUrl, 'GitHub', true) : '')
    . '</div>';

return [
    'pluginVersion' => 'v1.1.2',
    'pluginName' => 'EU Withdrawal Button',
    'pluginDescription' =>
        'The withdrawal function EU law requires of online stores from 19 June 2026 (Article 11a, Directive (EU) 2023/2673): '
        . 'a "Withdraw From Contract Here" button on every page, a short form with a Confirm Withdrawal button, '
        . 'and an emailed acknowledgment with the statement and its date and time. Works for guests, matches the order, '
        . 'and lists every withdrawal under Customers > Withdrawals.'
        . $euwLinks,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    // Plugins Library id. 0 until the Library assigns one (submissions are on
    // hold); it must be set before the first store installs from the Library.
    'pluginId' => 0,
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $euwGithubUrl,
    'pluginGroups' => [],
];
