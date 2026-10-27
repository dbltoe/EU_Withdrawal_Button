# Changelog

## [1.1.2] - 2026-10-27

- Plugin Manager only: once the plugin is installed, its panel has a Settings button that opens Configuration > EU Withdrawal Button. It isn't shown before Install or after Uninstall. The installer saves the panel again at Install, Upgrade and Uninstall, because Zen Cart 1.5.8, 2.0 and 2.1 otherwise keep the panel they saw first.
- The buttons are one row: ReadMe | Settings | Forum Thread | GitHub. "Read Me" is now "ReadMe" and "Forum Support Thread" is now "Forum Thread", and the buttons are a little narrower (8px side padding, 4px gaps) so all four fit on one line from a 1366px-wide window up. Nothing changes on the storefront or in the settings.

## [1.1.1] - 2026-10-06

- Documentation only: EU Withdrawal Button Pro is out, so the readme's Pro section and README.md now say what it does and where to get it instead of calling it planned. No code or setting changes.

## [1.1.0] - 2026-10-05

- The order confirmation email carries a "Withdraw From Contract Here" link to the withdrawal page, above the store's disclaimer (through `NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND`, same arguments 1.5.8 to 3.0.0). New setting Withdrawal Link in Order E-Mail?, on by default; under Selected Countries it follows the order's delivery (else billing) country, and an unknown country gets the link.
- Notifiers for add-ons (EU Withdrawal Button Pro) and stores' own observers, listed in `docs/CUSTOMIZING.md`: the page (`FORM_READ`, `SAVED`), each email's compose step, four template points and five on the admin page. With nothing listening the plugin works exactly as 1.0.0 did. An add-on's item lines become part of the statement, so the acknowledgment carries them.
- Preview Email builds both emails through the same method that sends them, so an add-on's additions show in the preview.
- A copy of `tpl_eu_withdrawal_default.php` made before 1.1.0 keeps working but lacks the template notifiers.

## [1.0.0] - 2026-10-05

First release.

- A "Withdraw From Contract Here" button in the footer of every page, with no template edits: inside the footer through `NOTIFY_FOOTER_AFTER_NAVSUPP` (2.1.0+, ZCA Bootstrap 3.8.0), else just before `</body>` through `NOTIFY_FOOTER_END` and moved up under the footer links.
- The withdrawal page (`main_page=eu_withdrawal`): name, order and email, an optional list of items, then a review screen with Confirm Withdrawal. No account needed; a logged-in customer's details and recent orders are filled in. One Page Checkout's guest placeholder is never treated as a customer.
- The acknowledgment of receipt (Article 11a(4)) with the statement and its date and time in the Store Time Zone, and the store's notice, reply-to the customer. Both saved after the statement, so a mail failure loses nothing; a failure is recorded and can be resent.
- Order matching: a customer's own order by number, anyone else's by number and email. Unmatched statements are saved and flagged, never refused.
- The spam trap holds a statement: saved, the store told, but nothing emailed to the address given and nothing written to the order until staff send the acknowledgment.
- A matched order's history note is hidden from the customer; an optional customer-visible line and status change.
- Customers > Withdrawals: list, filters, search, detail, status, staff note, Send Acknowledgment.
- Show Withdrawal Button To: All Visitors, or Selected Countries (EU 27 + IS, LI, NO by default) by the order's country, the customer's default address or a server variable (Cloudflare's by default). Unknown shows the button; the page always answers.
- The two legal labels, verbatim from the directive, for English, Dutch, Finnish, French, German, Italian and Spanish, chosen by language code. Other text falls back to English for a store language with no translation.
- Preview Email definitions for both emails.

[1.1.2]: https://github.com/dbltoe/EU_Withdrawal_Button/releases/tag/v1.1.2
[1.1.1]: https://github.com/dbltoe/EU_Withdrawal_Button/releases/tag/v1.1.1
[1.1.0]: https://github.com/dbltoe/EU_Withdrawal_Button/releases/tag/v1.1.0
[1.0.0]: https://github.com/dbltoe/EU_Withdrawal_Button/releases/tag/v1.0.0
