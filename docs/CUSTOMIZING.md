# Customizing EU Withdrawal Button

## Wording

Every word of the page and both emails is in
`zc_plugins/EuWithdrawalButton/v1.1.0/catalog/includes/languages/english/extra_definitions/lang.eu_withdrawal.php`.
Copy a constant into an override language file to change it. The admin text is in the same path under `admin/`.

That English file is the single source: Zen Cart loads a plugin's language files only from the folder of the session's language, with no English fallback, so the plugin fills any constant a language doesn't define from it.

## The two legal labels

`EU_WITHDRAWAL_LABEL_LINK` and `EU_WITHDRAWAL_LABEL_CONFIRM`. The law fixes the words ("withdraw from contract here", "confirm withdrawal", or an unambiguous corresponding formulation). For a session whose language code is `de`, `es`, `fi`, `fr`, `it` or `nl` the plugin uses the directive's own wording (`EuWithdrawalCore::LEGAL_LABELS`), unless that language's own file defines the constant.

## Translating

Copy the English file to `catalog/includes/languages/YOUR_LANGUAGE/extra_definitions/lang.eu_withdrawal.php` inside the plugin's folder and translate the values. Leave out the two label lines to keep the directive's wording.

## The page

Copy `catalog/includes/templates/default/templates/tpl_eu_withdrawal_default.php` into your template's `templates` folder. Change its look freely; keep the Confirm Withdrawal button's words. A copy made before 1.1.0 lacks the `NOTIFY_EU_WITHDRAWAL_TPL_*` lines (below); copy them over, or an add-on's fields won't show.

## The button

The footer button's markup and style come from `EuWithdrawalCore::buttonHtml()`. Its classes are `euw-wrap` and `euw-btn`; a store stylesheet can restyle them (keep it prominent, as the law asks).

## Hooks the plugin uses

| Notifier | Where | Why |
|---|---|---|
| `NOTIFY_FOOTER_AFTER_NAVSUPP` | footer, under its links (2.1.0+) | the button inside the footer |
| `NOTIFY_FOOTER_END` | just before `</body>` (every version) | the button where the first hook doesn't fire |
| `NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT` | `zen_mail()` | HTML for a guest's acknowledgment |
| `NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND` | `order.php`, the order confirmation email (same arguments 1.5.8 to 3.0.0) | the withdrawal link: text before the disclaimer, HTML in `$EMAIL_ORDER_MESSAGE` |
| `NOTIFY_EU_WITHDRAWAL_BUTTON` | Where you choose in your code | To allow you to place the button in an additional or alternative place |

To use the 'NOTIFY_EU_WITHDRAWAL_BUTTON' notifier place a notifier in your code where you want the button to appear.

```
$zco_notifier->notify('NOTIFY_EU_WITHDRAWAL_BUTTON', ['label' => 'extra', 'shown'=>false])
```
The array contains the `label` you want to use (any characters but do not use 'end' or 'footer') and `shown` is true or false. If true no further buttons are shown. If false then additional buttons will be shown including one of the default buttons.

The emails go through `zen_mail()` with the modules `eu_withdrawal_ack` and `eu_withdrawal_notice`, so other email hooks (and Preview Email Pro's templates) can tell them apart.

## Notifiers the plugin fires (1.1.0+)

For an add-on (EU Withdrawal Button Pro is built on these) or a store's own observer. All go through `$zco_notifier` as `notify($event, $data, &$p2, &$p3)`. Nothing listens unless something is installed, and the plugin works the same either way.

| Notifier | When | `$data` | By reference |
|---|---|---|---|
| `NOTIFY_EU_WITHDRAWAL_FORM_READ` | the form was posted (Review or Change Details), before the checks | `action`, `form`, `orders`, `customers_id` | `$p2` the add-on's data for this statement (an array, kept with it until it's confirmed); `$p3` the error messages (add a string to keep the customer on the form) |
| `NOTIFY_EU_WITHDRAWAL_SAVED` | the statement is saved; nothing sent yet | `withdrawal` (the saved row), `extra` (the add-on's data) | |
| `NOTIFY_EU_WITHDRAWAL_COMPOSE_ACK` | the acknowledgment is composed, before sending (and in Preview Email) | the statement row | `$p2` `['subject', 'text', 'html']` |
| `NOTIFY_EU_WITHDRAWAL_COMPOSE_NOTICE` | the same, for the store's notice | the statement row | `$p2` `['subject', 'text', 'html']` |
| `NOTIFY_EU_WITHDRAWAL_TPL_FORM_FIELDS` | the form, just before the items box | `form`, `orders`, `extra` | |
| `NOTIFY_EU_WITHDRAWAL_TPL_REVIEW_ROWS` | the review table, after its rows | `form`, `extra` | |
| `NOTIFY_EU_WITHDRAWAL_TPL_CHANGE_FIELDS` | inside the Change Details form (hidden fields go here) | `form`, `extra` | |
| `NOTIFY_EU_WITHDRAWAL_TPL_DONE` | the done page, after its message | `withdrawal` | |
| `NOTIFY_EU_WITHDRAWAL_ADMIN_POST` | a POST to Customers > Withdrawals whose `action` isn't the plugin's own (`status`, `note`, `send_ack`); the page then redirects back to the withdrawal | `action`, `withdrawal` | |
| `NOTIFY_EU_WITHDRAWAL_ADMIN_HEAD` | inside the admin page's `<head>` | `withdrawal` (or null on the list) | |
| `NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_HEAD` | the list's header row, before the Details column | | |
| `NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_ROW` | each list row, the matching cell | `withdrawal` | |
| `NOTIFY_EU_WITHDRAWAL_ADMIN_DETAIL` | one withdrawal's page, under its actions | `withdrawal` | |

The template notifiers print by echoing. An add-on's `items_lines` (an array of strings in its data) become part of the statement: they're saved before what the customer typed, so they're in the acknowledgment, the store's notice and the order's note. An admin field an add-on posts must be registered with Zen Cart's admin request sanitizer by the add-on (see this plugin's `admin/includes/extra_datafiles/eu_withdrawal_sanitizer.php`).
