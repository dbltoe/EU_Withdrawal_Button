# EU Withdrawal Button for Zen Cart

From 19 June 2026, online stores selling to consumers in the EU must let a customer withdraw from a purchase as easily as they made it, with a **withdrawal button** on the website (Article 11a of the EU Consumer Rights Directive, added by Directive (EU) 2023/2673). This plugin adds it to Zen Cart.

Runs on Zen Cart 1.5.8 through 3.0.0 and PHP 7.4 through 8.5 from one codebase, as an encapsulated plugin: no core or template files are changed.

## What's in it

- A **Withdraw From Contract Here** button in the footer of every page, placed by Zen Cart's own footer hooks.
- The withdrawal page: name, order and email, then a review screen with the **Confirm Withdrawal** button. No account needed; a logged-in customer picks from their recent orders, already filled in.
- An emailed **acknowledgment of receipt** with the statement and the date and time it was submitted, in the Store Time Zone. A notice to the store with every statement.
- Never refuses a withdrawal: a statement that doesn't match an order is saved for the store to check, and the spam trap holds a statement for staff instead of dropping it (and without emailing the address given).
- A matched order gets a history note hidden from the customer, and optionally a new status.
- Customers > Withdrawals: every statement, its status (Held, Received, In Progress, Done), a staff note, and Send Acknowledgment.
- The two labels the law fixes, in the directive's own words, for English, Dutch, Finnish, French, German, Italian and Spanish.
- Show Withdrawal Button To: all visitors, or only visitors in chosen countries (EU and EEA by default), by order, account or Cloudflare country.
- Preview Email definitions for both emails.

It gives a store the function Article 11a describes. It doesn't decide whether a withdrawal is valid, and it isn't legal advice.

**EU Withdrawal Button Pro** (sold separately at https://myzencartzone.com/index.php?main_page=product_info&products_id=41) turns withdrawals into a returns portal: items chosen by the customer, RMA numbers and return labels, refunds and store credit recorded, a Track Your Return page, the EU model withdrawal form in order emails, and an optional returns portal for customers outside the EU.

## Documentation

- `readme.html`: the store owner's guide (also linked from Plugin Manager).
- `docs/INSTALL.md`: installing, upgrading, uninstalling.
- `docs/CONFIGURATION.md`: every setting.
- `docs/CUSTOMIZING.md`: wording, languages, the page, and the hooks.
- `docs/COMPATIBILITY.md`: versions, templates, what the code relies on and what was tested where.
- `CHANGELOG.md`: changes by version.

## Layout

- `zc_plugins/EuWithdrawalButton/v1.1.2/`: the plugin, exactly as it's uploaded.
- `readme.html`: a copy of the plugin's readme at the top of the download.

## Support

Ask in the support thread on the Zen Cart forum: https://www.zen-cart.com/threads/207395. Bugs can also be reported in this repository's issue tracker.

## License

GNU General Public License v2.0. Copyright (c) 2026 My Zen Cart Host (dbltoe).
