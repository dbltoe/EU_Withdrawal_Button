<?php
/**
 * EU Withdrawal Button -- storefront text, including both emails.
 *
 * The single source of the plugin's wording: EuWithdrawalCore::text() falls
 * back to this file for any constant a store's own language doesn't define
 * (Zen Cart loads a plugin's language files only from the session language's
 * folder), and the admin side reads it when it resends or previews an email.
 *
 * To translate: copy this file to
 *   catalog/includes/languages/YOUR_LANGUAGE/extra_definitions/lang.eu_withdrawal.php
 * in this plugin's folder (YOUR_LANGUAGE is the store's language folder, for
 * example "spanish") and translate the values. Leave out the two LABEL lines
 * to keep the directive's own wording for your language.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

$define = [
    // Article 11a's two labels. The law fixes the words ("withdraw from contract
    // here", "confirm withdrawal", or an unambiguous corresponding formulation);
    // English buttons are in Title Case. For German, Spanish, Finnish, French,
    // Italian and Dutch the plugin uses the directive's own wording unless a
    // language file defines these two.
    'EU_WITHDRAWAL_LABEL_LINK' => 'Withdraw From Contract Here',
    'EU_WITHDRAWAL_LABEL_CONFIRM' => 'Confirm Withdrawal',

    // The withdrawal page.
    'EU_WITHDRAWAL_PAGE_TITLE' => 'Withdraw From Contract',
    'EU_WITHDRAWAL_PAGE_INTRO' => 'Use this form to withdraw from a purchase within your withdrawal period. You don\'t need an account. You\'ll check your details on the next screen before sending.',
    'EU_WITHDRAWAL_INPUT_NAME' => 'Your name',
    'EU_WITHDRAWAL_INPUT_ORDERS' => 'Which order?',
    'EU_WITHDRAWAL_INPUT_ORDER_LINE' => 'Order #%1$s, %2$s, %3$s',
    'EU_WITHDRAWAL_INPUT_ORDER_OTHER' => 'Another order (type its number below)',
    'EU_WITHDRAWAL_INPUT_ORDER' => 'Order number',
    'EU_WITHDRAWAL_INPUT_ORDER_IF_NOT_LISTED' => 'Order number (if not listed above)',
    'EU_WITHDRAWAL_INPUT_EMAIL' => 'Email address for your confirmation',
    'EU_WITHDRAWAL_INPUT_ITEMS' => 'Withdrawing from only some items? List them here (optional)',
    'EU_WITHDRAWAL_BUTTON_REVIEW' => 'Review My Withdrawal',
    'EU_WITHDRAWAL_BUTTON_CHANGE' => 'Change Details',
    'EU_WITHDRAWAL_REVIEW_INTRO' => 'Please check your withdrawal statement, then click %s to send it.',
    'EU_WITHDRAWAL_PRIVACY' => 'We use these details only to process your withdrawal. See our %s.',
    'EU_WITHDRAWAL_PRIVACY_LINK' => 'Privacy Notice',
    'EU_WITHDRAWAL_DONE' => 'We\'ve received your withdrawal. Reference %1$s, submitted %2$s.',
    'EU_WITHDRAWAL_DONE_EMAILED' => 'We\'ve emailed a confirmation of receipt to %s. It confirms we received your withdrawal; we\'ll contact you about the next steps.',
    'EU_WITHDRAWAL_DONE_HELD' => 'We\'ll email a confirmation of receipt to %s shortly. It confirms we received your withdrawal; we\'ll contact you about the next steps.',
    'EU_WITHDRAWAL_ERR_NAME' => 'Please enter your name.',
    'EU_WITHDRAWAL_ERR_EMAIL' => 'Please enter the email address we should send the confirmation to.',
    'EU_WITHDRAWAL_ERR_ORDER' => 'Please tell us which order you are withdrawing from (the order number is in your order confirmation email).',
    'EU_WITHDRAWAL_ERR_EXPIRED' => 'Please fill in the form again; it was already sent or it expired.',
    'EU_WITHDRAWAL_ERR_SAVE' => 'Sorry, your withdrawal couldn\'t be saved. Please try again, or email us at %s with your name, order number and that you are withdrawing from the contract.',

    // Shared by the page and the emails.
    'EU_WITHDRAWAL_FIELD_REFERENCE' => 'Reference',
    'EU_WITHDRAWAL_FIELD_NAME' => 'Name',
    'EU_WITHDRAWAL_FIELD_ORDER' => 'Order',
    'EU_WITHDRAWAL_FIELD_ITEMS' => 'What you\'re withdrawing from',
    'EU_WITHDRAWAL_FIELD_EMAIL' => 'Confirmation sent to',
    'EU_WITHDRAWAL_FIELD_SUBMITTED' => 'Submitted',
    'EU_WITHDRAWAL_WHOLE_ORDER' => 'The whole order',

    // The customer's acknowledgment of receipt. %s in the subject is the reference (W12).
    'EU_WITHDRAWAL_ACK_SUBJECT' => 'We\'ve received your withdrawal (ref. %s)',
    'EU_WITHDRAWAL_ACK_INTRO' => 'We\'ve received your withdrawal from the contract.',
    'EU_WITHDRAWAL_ACK_RECEIPT_ONLY' => 'This email confirms that we received it. It isn\'t a decision on your withdrawal; we\'ll contact you about the next steps.',
    'EU_WITHDRAWAL_ACK_HEADING' => 'Your withdrawal statement',

    // The store's notice.
    'EU_WITHDRAWAL_NOTICE_SUBJECT_ORDER' => 'Withdrawal received: %1$s for order #%2$s',
    'EU_WITHDRAWAL_NOTICE_SUBJECT_UNMATCHED' => 'Withdrawal received: %s (not matched to an order)',
    'EU_WITHDRAWAL_NOTICE_SUBJECT_HELD' => '[held: spam check]',
    'EU_WITHDRAWAL_NOTICE_INTRO' => 'A customer has sent a withdrawal statement through your store\'s withdrawal page.',
    'EU_WITHDRAWAL_NOTICE_MATCHED' => 'It matches order #%s; a note was added to that order\'s history (hidden from the customer).',
    'EU_WITHDRAWAL_NOTICE_UNMATCHED' => 'It doesn\'t match an order (they entered "%s" with that email address). Find the order before acting.',
    'EU_WITHDRAWAL_NOTICE_ACK_SENT' => 'The customer was emailed an acknowledgment of receipt with the statement and its date and time.',
    'EU_WITHDRAWAL_NOTICE_HELD' => 'The page\'s spam trap was filled in, so it\'s held: nothing was emailed to that address and nothing was written to the order. If it\'s genuine, open it on the Withdrawals page (under Customers in your admin) and send the acknowledgment from there.',
    'EU_WITHDRAWAL_NOTICE_NEXT' => 'The acknowledgment confirms receipt only. Decide whether the withdrawal is valid (deadline, exceptions) and reply to the customer. The Withdrawals page under Customers in your admin has the full record.',

    // The order's history.
    'EU_WITHDRAWAL_STAFF_NOTE' => 'EU withdrawal received %1$s (ref. %2$s) for %3$s. This acknowledges receipt only; review the withdrawal before acting.',
    'EU_WITHDRAWAL_STAFF_NOTE_WHOLE' => 'the whole order',
    'EU_WITHDRAWAL_STAFF_NOTE_ITEMS' => 'these items: %s',
    'EU_WITHDRAWAL_CUSTOMER_NOTE' => 'Withdrawal received on %s.',
    'EU_WITHDRAWAL_UPDATED_BY' => 'EU Withdrawal Button',

    // The order confirmation email (Withdrawal Link in Order Email?), above the
    // link labeled with the legal label.
    'EU_WITHDRAWAL_ORDER_EMAIL_INTRO' => 'To withdraw from this order, use our withdrawal page:',

    // Recorded on the statement when zen_mail() sent nothing (shown in the admin).
    'EU_WITHDRAWAL_MAIL_NOT_SENT' => 'Not sent: Send E-Mails is off, this email type is in EMAIL_MODULES_TO_SKIP, or the name or address held a line break.',
];

return $define;
