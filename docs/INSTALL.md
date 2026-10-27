# Installing EU Withdrawal Button

## Requirements

- Zen Cart 1.5.8 through 3.0.0, PHP 7.4 through 8.5.
- Email sending working (Configuration > E-Mail Options > Send E-Mails is true): the acknowledgment is required by law.
- Configuration > My Store > Store Address and Phone filled in: it goes on every acknowledgment.

## Install

1. Upload `zc_plugins/EuWithdrawalButton` into your store's `zc_plugins` folder, so `zc_plugins/EuWithdrawalButton/v1.1.2/manifest.php` exists.
2. Modules > Plugin Manager > EU Withdrawal Button > **Install**.
3. Configuration > EU Withdrawal Button (or the plugin's **Settings** button in Plugin Manager): set **Store Time Zone** (for example `Europe/Berlin`); hosting servers often run on UTC.
4. Check the storefront footer for the button, and send one test withdrawal for an order of your own.

The installer refuses, before writing anything, when the database already has an `eu_withdrawals` table that belongs to something else.

## Upgrade

Upload the new version's folder beside the old one, then Plugin Manager > **Upgrade**. Settings and statements are kept.

## Uninstall

Plugin Manager > **Uninstall**. With Delete Withdrawal Records on Uninstall? false (the default) the statements stay, and a re-install picks them up.
