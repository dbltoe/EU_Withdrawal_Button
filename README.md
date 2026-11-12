# EU Withdrawal Button

Version 1.1.3 · Zen Cart 1.5.8 through 3.0.0 · PHP 7.4 through 8.5 · GPL v3 · by My Zen Cart Host (dbltoe)

The withdrawal button EU law requires of online stores from 19 June 2026 (Article 11a of the EU Consumer Rights Directive), for Zen Cart: an encapsulated plugin, with no core or template files changed.

## What it does

From 19 June 2026, online stores selling to consumers in the EU must let a customer withdraw from a purchase as easily as they made it: with a **withdrawal button** on the website. This plugin adds it.

- A **Withdraw From Contract Here** button in the footer of every page. No template edits.
- A short withdrawal page: name, order and email, then a review screen with the **Confirm Withdrawal** button. No account needed.
- An emailed **acknowledgment of receipt** with the statement and the date and time it was sent.
- The order is found for you and gets a note in its history; **Customers > Withdrawals** lists every statement.
- The two labels the law fixes are built in for English, German, Spanish, Finnish, French, Italian and Dutch, in the directive's own words.
- Installs and uninstalls from Plugin Manager. No core edits.

> This plugin gives your store the withdrawal function Article 11a describes. It doesn't decide whether a withdrawal is valid (the deadline, or exceptions such as custom-made goods), and it isn't legal advice. National laws differ in their details; check yours with a lawyer.

## What the law asks

Directive (EU) 2023/2673 adds Article 11a to the EU Consumer Rights Directive. For contracts made through a website, the trader must offer a *withdrawal function*:

1. Labeled "withdraw from contract here" (or an unambiguous equivalent), easy to read, **prominently displayed**, easy to reach, and available throughout the withdrawal period.
2. It lets the customer send an online withdrawal statement giving their **name**, the **contract** (the order) and the **email** for the confirmation.
3. They submit it with a button labeled only "confirm withdrawal" (or an unambiguous equivalent).
4. The trader then sends, without undue delay, an **acknowledgment of receipt** on a durable medium (email), with the statement's content and the **date and time** it was submitted.
5. A statement sent before the withdrawal period ends counts as on time.

The plugin does each of these. Guidance from consumer-law firms adds: no login required, ask for nothing beyond the three details (a box for which items is fine), no CAPTCHA, never limit how many can be sent, and make clear the email confirms receipt, not approval. The plugin follows all of it.

## Requirements

- Zen Cart 1.5.8 through 3.0.0, PHP 7.4 through 8.5.
- Email sending working (Configuration > E-Mail Options > Send E-Mails is true). The acknowledgment is the law's own requirement.
- Your store's name and address filled in (Configuration > My Store > Store Address and Phone); the acknowledgment carries them.

## Installing

1. Upload the `zc_plugins/EuWithdrawalButton` folder from this package into your store's `zc_plugins` folder, so you have `zc_plugins/EuWithdrawalButton/v1.1.3/manifest.php`.
2. In the admin, open **Modules > Plugin Manager**, select **EU Withdrawal Button** and click **Install**.
3. Set **Store Time Zone** under **Configuration > EU Withdrawal Button** (for example `Europe/Berlin`). Hosting servers often run on UTC. The plugin's **Settings** button in Plugin Manager opens the same page.
4. Look at your storefront: the button is in the footer. Try the page once with a real order of your own.

Once it's installed, a superuser can open this guide from the **ReadMe** button in Plugin Manager.

## Configuration

Under **Configuration > EU Withdrawal Button**:

| Setting | Default | What it does |
| --- | --- | --- |
| Enable the Withdrawal Button? | true | The button and the page. Off, the page sends visitors to your home page. |
| Show Withdrawal Button To | All Visitors | All Visitors, or only visitors in Selected Countries. See Who sees the button. |
| Selected Countries | EU 27, IS, LI, NO | Two-letter codes, comma-separated. |
| Country Server Variable | HTTP_CF_IPCOUNTRY | Where a guest's country comes from (Cloudflare's header by default). |
| Who the Page Is For (Optional) | blank | A line above the form, for example "For customers in the EU, Iceland, Liechtenstein and Norway." |
| Withdrawal Link in Order E-Mail? | true | Adds a "Withdraw From Contract Here" link to the order confirmation email, above your disclaimer. It follows Show Withdrawal Button To, by the order's delivery country (else billing). See The emails. |
| Store Time Zone | blank (PHP's) | The time zone of the date and time in the acknowledgment. |
| Notify E-Mail Address | blank (store owner) | Who gets the store's notice of each withdrawal. Several addresses, comma-separated. |
| Orders to List (Days) | 90 | A logged-in customer chooses from their orders of the last this-many days. |
| Order Status on Withdrawal | 0 (unchanged) | A matched order moves to this status. No email goes to the customer. |
| Show the Customer a Note on Their Order? | false | Adds "Withdrawal received on (date and time)." to the order history the customer sees. |
| Keep Done Withdrawals (Days) | 0 (keep) | Withdrawals marked Done are deleted after this many days. They record when a customer withdrew; keep them unless you have a reason not to. |
| Delete Withdrawal Records on Uninstall? | false | When false, uninstalling keeps every statement. |

## The button

The button sits in the footer, just under the footer links, on every page except the withdrawal page itself. On a customer's order page (My Account > order) it opens the form with that order already chosen.

- On Zen Cart 2.1.0 and later, and with ZCA Bootstrap, it's placed inside the footer by Zen Cart's own footer hook.
- On 1.5.8 and 2.0 (and any template without that hook) it's placed at the end of the page and then moved up under the footer links when the page has them.
- It's white on dark blue (10:1 contrast), keyboard-focusable, and doesn't move on hover.

## Who sees the button

**All Visitors** is the default and the safe choice. A store that sells to the EU but doesn't want the button in, say, the US can choose **Selected Countries**. The visitor's country is, first answer wins:

1. on a customer's order page: that order's delivery country, else its billing country;
2. a logged-in customer: their default address's country;
3. a guest: the country code in the *Country Server Variable* (Cloudflare sends one with every request; some hosts' GeoIP modules do too);
4. not known: **the button shows**.

Only the footer button is hidden. The withdrawal page always works for anyone who reaches it, and no statement is ever refused because of country.

> Without Cloudflare or a host GeoIP module there is no guest country, so guests always see the button. IP country is also sometimes wrong (a customer traveling, or on a VPN). Whether customers outside the EU have a right of withdrawal at all depends on your terms and governing law (many German stores give it to everyone): ask your lawyer.

## The withdrawal page

Reached from the button, or at `index.php?main_page=eu_withdrawal`.

- **Guests** give their name, the order number and their email. No account or login.
- **Logged-in customers** find their name and email filled in and pick the order from a list of their recent orders.
- An optional box says which items, if not the whole order.
- A review screen shows the statement back with the **Confirm Withdrawal** button and a line pointing to your Privacy Notice; **Change Details** goes back to the form.
- After confirming, the page shows the reference (W12) and the date and time it was received.

The page is never indexed by search engines. Sending twice (a double click, the browser's Back button) doesn't make a second statement.

## The emails

### To the customer: the acknowledgment

Sent at once. It says the withdrawal was received and that it isn't a decision, then repeats the statement: reference, name, order, what they're withdrawing from, the address it went to, and the date and time submitted in your Store Time Zone (for example `2026-10-05 08:15:56 CEST (UTC+02:00)`), followed by your store's name and address. Guests get it as HTML; a customer who chose text-only gets text.

### To the store: the notice

Sent to the Notify E-Mail Address (or the store owner) for every withdrawal. It says whether the statement matched an order, whether the acknowledgment went out, and what to do next. Reply-to is the customer, so you can answer straight from it.

Both are in Tools > Preview Email (with the free Preview Email plugin), under EU Withdrawal Button.

### In the order confirmation email

Every order confirmation email carries a short line, "To withdraw from this order, use our withdrawal page:", with a **Withdraw From Contract Here** link to it, just above your store's disclaimer. It's where customers look first. The link uses the same label as the button, in the customer's language. Under *Selected Countries* it goes only to orders delivered (or, for downloads, billed) to those countries; when the country can't be told, it's included. Turn it off with *Withdrawal Link in Order E-Mail?*.

### The spam trap

The form has a hidden field only robots fill in. A statement that fills it is still saved, but **held**: nothing is emailed to the address given and nothing is written to the order, so the form can't be used to send email to strangers. You're notified; if a held statement is genuine, open it and click **Send Acknowledgment**.

## Matching the order

- A logged-in customer's own order matches by its number.
- Anyone else's matches when the order number and the email address agree (capitals don't matter).
- **A statement that doesn't match is still saved** and marked "not matched": refusing it would make withdrawing harder than buying. Find the order yourself before acting. The customer's acknowledgment shows only what they typed, so nothing about an order is revealed to someone who guessed its number.

A matched order gets a note in its history: "EU withdrawal received (date and time) (ref. W12) for the whole order..." It's **hidden from the customer**. Optionally the order also moves to a status you choose, and the customer sees a short "Withdrawal received on..." line.

One Page Checkout guests all share one placeholder customer record; the page never treats a guest as that customer, so no guest is ever shown another guest's orders.

## Customers > Withdrawals

- Every statement, newest first, filtered by status (Held, Received, In Progress, Done) or searched by name, email or order.
- Each one shows the statement exactly as sent, the time in your zone and in UTC, the customer's account if they had one, their IP address and country, whether the acknowledgment went out, and whether the order was noted.
- Set the status, keep a staff note, and send (or resend) the acknowledgment.

## Languages

The two labels the law fixes are built in, in the directive's own wording, for the EU languages that have a Zen Cart language pack, plus German:

| Language | The button | The confirm button |
| --- | --- | --- |
| English | Withdraw From Contract Here | Confirm Withdrawal |
| Dutch | Hier de overeenkomst herroepen | Herroeping bevestigen |
| Finnish | Peruuta sopimus tästä | Vahvista peruuttaminen |
| French | Renoncer au contrat ici | Confirmer la rétractation |
| German | Vertrag widerrufen | Widerruf bestätigen |
| Italian | Recedere dal contratto qui | Conferma recesso |
| Spanish | Desistir del contrato aquí | Confirmar desistimiento |

They follow the language's two-letter code, whatever your language folder is called. The rest of the wording is English until translated: copy `catalog/includes/languages/english/extra_definitions/lang.eu_withdrawal.php` (inside the plugin's folder) to the same place under your language's folder and translate the values. Leave out the two label lines to keep the directive's wording.

## EU Withdrawal Button Pro

A paid add-on, **EU Withdrawal Button Pro**, turns each withdrawal into a return you can follow:

- customers choose the items and quantities they're withdrawing from, with a reason for each;
- a Return panel on each withdrawal, with an RMA number, return instructions and a PDF return label, emailed to the customer;
- Return Received with an optional restock, and the refund recorded (or store credit as a gift certificate);
- a Track Your Return page the customer opens from every email;
- the EU model withdrawal form in every order confirmation email;
- deadline flags and Article 16 reminders;
- an optional returns portal for customers outside the EU;
- a report by month and by product.

It needs this plugin, version 1.1.0 or later, installed. It's at myzencartzone.com (https://myzencartzone.com/index.php?main_page=product_info&products_id=41). This plugin is complete on its own.

## Customizing

- **Wording:** every word of the page and the emails is in `catalog/includes/languages/english/extra_definitions/lang.eu_withdrawal.php`. Copy a constant into your own override language file to change it.
- **The page:** copy `tpl_eu_withdrawal_default.php` into your template's `templates` folder. Change its look freely; keep the Confirm Withdrawal button's words.
- **The emails' look:** they use your store's email template, or Preview Email Pro if you have it.
- **Your own code:** the page, the emails and the admin page fire notifiers (`NOTIFY_EU_WITHDRAWAL_*`) that an observer can attach to; EU Withdrawal Button Pro is built on them. They're listed in `docs/CUSTOMIZING.md` in the download. A copy of the page's template made before version 1.1.0 lacks the template's ones; copy the new lines over.

## Upgrading

Upload the new version's folder beside the old one and click **Upgrade** in Plugin Manager. Your settings and every withdrawal statement are kept. Then **Clean Up** removes the old version's folder.

From 1.0.0 to 1.1.0, one setting is new: *Withdrawal Link in Order E-Mail?*, which arrives switched on. From 1.1.0 to 1.1.1, only this guide changes; there's nothing to set. From 1.1.1 to 1.1.2, only the plugin's panel in Plugin Manager changes. From 1.1.2 to 1.1.3, the license becomes the GNU General Public License, version 3, and this guide replaces readme.html: the ReadMe button in Plugin Manager opens it inside the admin, for superusers, in its own window. Nothing changes on the storefront or in the settings.

## Uninstalling

Click **Uninstall** in Plugin Manager. With *Delete Withdrawal Records on Uninstall?* false (the default) every statement is kept, and a later install picks them up again. Remember the law still applies to your store without the button.

## Troubleshooting

| What you see | What to check |
| --- | --- |
| No button in the footer | Enable the Withdrawal Button? is true; Show Withdrawal Button To is All Visitors (or your country is listed). The button isn't shown on the withdrawal page itself. |
| The acknowledgment wasn't sent | Customers > Withdrawals shows why (for example Send E-Mails is off). Fix it, then open the withdrawal and click Send Acknowledgment. |
| The time in the email is wrong | Set Store Time Zone (for example Europe/Madrid). |
| A statement shows "Held" | The spam trap was filled in. If it's genuine, open it and click Send Acknowledgment. |
| "not matched" | The order number and email didn't agree with an order. Find the order yourself; the statement still counts. |
| Guests outside the EU still see the button | Without Cloudflare or a host GeoIP module a guest's country is unknown, and unknown shows the button. |

## Support

Support is through the plugin's support thread on the Zen Cart forum (https://www.zen-cart.com/threads/207395) and myzencartzone.com (https://myzencartzone.com). The source, releases and issue tracker are on GitHub at github.com/dbltoe/EU_Withdrawal_Button (https://github.com/dbltoe/EU_Withdrawal_Button).

The download also has `docs/INSTALL.md`, `docs/CONFIGURATION.md`, `docs/CUSTOMIZING.md` and `docs/COMPATIBILITY.md` for more detail.

## Changelog

See `CHANGELOG.md`. Version 1.1.3 moves the plugin to the GNU General Public License, version 3, and replaces readme.html with this guide, opened from the ReadMe button in Plugin Manager.

## License

EU Withdrawal Button is free software under the GNU General Public License, version 3 (https://www.gnu.org/licenses/gpl-3.0.html); the full text is in `LICENSE`. Copyright © 2026 My Zen Cart Host (dbltoe).
