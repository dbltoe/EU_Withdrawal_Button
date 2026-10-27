<?php
/**
 * EU Withdrawal Button -- admin text (English).
 *
 * In extra_definitions so the menu labels exist before the menus are drawn.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

$define = [
    'BOX_CONFIGURATION_EU_WITHDRAWAL_BUTTON' => 'EU Withdrawal Button',
    'BOX_CUSTOMERS_EU_WITHDRAWALS' => 'Withdrawals',

    // Customers > Withdrawals
    'EU_WITHDRAWAL_ADMIN_HEADING' => 'Withdrawals',
    'EU_WITHDRAWAL_ADMIN_DETAIL_HEADING' => 'Withdrawal %s',
    'EU_WITHDRAWAL_ADMIN_BACK' => 'Back to Withdrawals',
    'EU_WITHDRAWAL_ADMIN_INTRO' => 'Withdrawal statements customers sent through the "Withdraw From Contract Here" button. The customer was sent an acknowledgment of receipt; whether each withdrawal is valid (deadline, exceptions) is for you to decide.',
    'EU_WITHDRAWAL_ADMIN_OFF' => 'The withdrawal button is turned off (Configuration > EU Withdrawal Button > Enable the Withdrawal Button?). Stores selling to EU consumers need it on.',
    'EU_WITHDRAWAL_ADMIN_HELD_WARNING' => '%u held: the spam trap was filled in, so nothing was emailed. Open each one and send the acknowledgment if it\'s genuine.',

    'EU_WITHDRAWAL_ADMIN_ALL' => 'All',
    'EU_WITHDRAWAL_ADMIN_SEARCH' => 'Name, E-Mail or Order',
    'EU_WITHDRAWAL_ADMIN_BUTTON_SEARCH' => 'Search',
    'EU_WITHDRAWAL_ADMIN_BUTTON_RESET' => 'Reset',
    'EU_WITHDRAWAL_ADMIN_NONE' => 'No withdrawals match.',
    'EU_WITHDRAWAL_ADMIN_SHOWING' => 'Showing %u to %u of %u',
    'EU_WITHDRAWAL_ADMIN_PREVIOUS' => 'Previous',
    'EU_WITHDRAWAL_ADMIN_NEXT' => 'Next',

    'EU_WITHDRAWAL_ADMIN_COL_REF' => 'Reference',
    'EU_WITHDRAWAL_ADMIN_COL_SUBMITTED' => 'Submitted',
    'EU_WITHDRAWAL_ADMIN_COL_CUSTOMER' => 'Customer',
    'EU_WITHDRAWAL_ADMIN_COL_ORDER' => 'Order',
    'EU_WITHDRAWAL_ADMIN_COL_ITEMS' => 'Withdrawing From',
    'EU_WITHDRAWAL_ADMIN_COL_STATUS' => 'Status',
    'EU_WITHDRAWAL_ADMIN_COL_ACK' => 'Acknowledgment',
    'EU_WITHDRAWAL_ADMIN_BUTTON_DETAILS' => 'Details',
    'EU_WITHDRAWAL_ADMIN_NOT_MATCHED' => '%s (not matched)',

    'EU_WITHDRAWAL_ADMIN_STATUS_HELD' => 'Held',
    'EU_WITHDRAWAL_ADMIN_STATUS_RECEIVED' => 'Received',
    'EU_WITHDRAWAL_ADMIN_STATUS_IN_PROGRESS' => 'In Progress',
    'EU_WITHDRAWAL_ADMIN_STATUS_DONE' => 'Done',

    'EU_WITHDRAWAL_ADMIN_ACK_SENT' => 'Sent %s',
    'EU_WITHDRAWAL_ADMIN_ACK_FAILED' => 'Not sent: %s',
    'EU_WITHDRAWAL_ADMIN_ACK_HELD' => 'Not sent (held)',

    'EU_WITHDRAWAL_ADMIN_FIELD_SUBMITTED_UTC' => 'Submitted (UTC)',
    'EU_WITHDRAWAL_ADMIN_FIELD_EMAIL' => 'E-Mail',
    'EU_WITHDRAWAL_ADMIN_FIELD_CUSTOMER_ACCOUNT' => 'Customer Account',
    'EU_WITHDRAWAL_ADMIN_FIELD_LANGUAGE' => 'Language',
    'EU_WITHDRAWAL_ADMIN_FIELD_IP' => 'IP Address',
    'EU_WITHDRAWAL_ADMIN_FIELD_COUNTRY' => 'Country',
    'EU_WITHDRAWAL_ADMIN_FIELD_ORDER_NOTE' => 'Order History Note',
    'EU_WITHDRAWAL_ADMIN_ORDER_NOTE_DONE' => 'Added (hidden from the customer)',
    'EU_WITHDRAWAL_ADMIN_ORDER_NOTE_NONE' => 'None',
    'EU_WITHDRAWAL_ADMIN_GUEST' => 'Guest',
    'EU_WITHDRAWAL_ADMIN_UNKNOWN' => 'Unknown',
    'EU_WITHDRAWAL_ADMIN_STATEMENT' => 'The Statement as Sent',

    'EU_WITHDRAWAL_ADMIN_SET_STATUS' => 'Status',
    'EU_WITHDRAWAL_ADMIN_BUTTON_SAVE_STATUS' => 'Save Status',
    'EU_WITHDRAWAL_ADMIN_NOTE' => 'Staff Note',
    'EU_WITHDRAWAL_ADMIN_BUTTON_SAVE_NOTE' => 'Save Note',
    'EU_WITHDRAWAL_ADMIN_BUTTON_SEND_ACK' => 'Send Acknowledgment',
    'EU_WITHDRAWAL_ADMIN_SEND_ACK_HELP_HELD' => 'Sends the customer the acknowledgment of receipt and adds the note to the matched order. Do this if the statement is genuine.',
    'EU_WITHDRAWAL_ADMIN_SEND_ACK_HELP_FAILED' => 'Sends the acknowledgment of receipt again.',

    'EU_WITHDRAWAL_ADMIN_MSG_STATUS' => 'Status saved.',
    'EU_WITHDRAWAL_ADMIN_MSG_NOTE' => 'Note saved.',
    'EU_WITHDRAWAL_ADMIN_MSG_ACK_SENT' => 'Acknowledgment sent to %s.',
    'EU_WITHDRAWAL_ADMIN_MSG_ACK_FAILED' => 'The acknowledgment could not be sent: %s',
    'EU_WITHDRAWAL_ADMIN_ERR_NOT_FOUND' => 'That withdrawal record no longer exists.',
    'EU_WITHDRAWAL_ADMIN_ERR_STATUS' => 'That status change isn\'t possible. A held withdrawal leaves Held when its acknowledgment is sent.',
];

return $define;
