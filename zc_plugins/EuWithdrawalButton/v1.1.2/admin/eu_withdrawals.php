<?php
/**
 * EU Withdrawal Button -- Customers > Withdrawals.
 *
 *   index.php?cmd=eu_withdrawals              the list: counts by status, search, pages
 *   index.php?cmd=eu_withdrawals&wID=12       one withdrawal: the statement as sent
 *
 * The actions (status, note, send the acknowledgment) are POSTs carrying
 * `action` and `wID`. Core's init_sessions refuses any admin POST without the
 * session's securityToken before this file runs (every release 1.5.8 ->
 * 3.0.0); zen_draw_form() adds the token.
 *
 * Declares no functions: the work is in the shared classes.
 *
 * Seams for an add-on (EU Withdrawal Button Pro), listed in docs/CUSTOMIZING.md:
 *   NOTIFY_EU_WITHDRAWAL_ADMIN_POST         a POST whose action isn't one of ours
 *   NOTIFY_EU_WITHDRAWAL_ADMIN_HEAD         inside <head>
 *   NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_HEAD    a column heading on the list
 *   NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_ROW     that column's cell, per withdrawal
 *   NOTIFY_EU_WITHDRAWAL_ADMIN_DETAIL       under one withdrawal's actions
 * An add-on registers its own fields with the admin request sanitizer.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

// Reached only as admin/index.php?cmd=eu_withdrawals, and index.php loads the
// bootstrap (which defines IS_ADMIN_FLAG) before routing here.
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

require 'includes/application_top.php';
// Guarded: on a version upgrade the old version's files may already have
// loaded these classes from the other version folder ("Cannot redeclare class").
if (!class_exists('EuWithdrawalStore', false)) {
    require_once __DIR__ . '/../shared/EuWithdrawalStore.php';
}
if (!class_exists('EuWithdrawalMailer', false)) {
    require_once __DIR__ . '/../shared/EuWithdrawalMailer.php';
}

$euwStore = new EuWithdrawalStore($db);
$euwH = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, CHARSET);
};
$euwStatusLabel = static function (string $status): string {
    $key = 'EU_WITHDRAWAL_ADMIN_STATUS_' . strtoupper($status);
    return defined($key) ? constant($key) : $status;
};
$euwAckText = static function (array $row): string {
    if ($row['status'] === 'held') {
        return EU_WITHDRAWAL_ADMIN_ACK_HELD;
    }
    if ((string)$row['ack_sent_utc'] !== EuWithdrawalCore::NEVER) {
        // In the zone the statement was taken in, beside its Submitted time.
        $tz = (string)$row['timezone'] !== '' ? (string)$row['timezone'] : EuWithdrawalCore::timezone();
        return sprintf(EU_WITHDRAWAL_ADMIN_ACK_SENT, EuWithdrawalCore::localTime((string)$row['ack_sent_utc'], $tz));
    }
    return sprintf(EU_WITHDRAWAL_ADMIN_ACK_FAILED, $row['ack_error'] !== '' ? $row['ack_error'] : '-');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') !== '') {
    $euwPostId = (int)($_POST['wID'] ?? 0);
    $euwRow = $euwStore->find($euwPostId);
    if ($euwRow === null) {
        $messageStack->add_session(EU_WITHDRAWAL_ADMIN_ERR_NOT_FOUND, 'error');
        zen_redirect(zen_href_link(FILENAME_EU_WITHDRAWALS, '', 'SSL'));
    }
    $euwPostAction = (string)$_POST['action'];
    if (!in_array($euwPostAction, ['status', 'note', 'send_ack'], true)) {
        // Not ours: an add-on's form on this page. It says what happened in the
        // message stack; nothing is done here.
        EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_ADMIN_POST', ['action' => $euwPostAction, 'withdrawal' => $euwRow]);
    } elseif ($euwPostAction === 'status') {
        if ($euwStore->setStatus($euwPostId, (string)($_POST['status'] ?? ''))) {
            $messageStack->add_session(EU_WITHDRAWAL_ADMIN_MSG_STATUS, 'success');
        } else {
            $messageStack->add_session(EU_WITHDRAWAL_ADMIN_ERR_STATUS, 'error');
        }
    } elseif ($euwPostAction === 'note') {
        $euwStore->setNote($euwPostId, trim((string)($_POST['admin_note'] ?? '')));
        $messageStack->add_session(EU_WITHDRAWAL_ADMIN_MSG_NOTE, 'success');
    } else {
        // A held statement judged genuine: Received, its order noted, then emailed.
        if ($euwRow['status'] === 'held') {
            $euwRow = $euwStore->release($euwPostId);
            if ($euwRow !== null && (int)$euwRow['osh_id'] === 0) {
                $euwStore->noteOrder($euwRow);
            }
        }
        if ($euwRow !== null) {
            $euwError = EuWithdrawalMailer::sendAcknowledgment($euwRow);
            $euwStore->markAcknowledged($euwPostId, $euwError);
            if ($euwError === '') {
                $messageStack->add_session(sprintf(EU_WITHDRAWAL_ADMIN_MSG_ACK_SENT, $euwH($euwRow['email'])), 'success');
            } else {
                $messageStack->add_session(sprintf(EU_WITHDRAWAL_ADMIN_MSG_ACK_FAILED, $euwH($euwError)), 'error');
            }
        }
    }
    zen_redirect(zen_href_link(FILENAME_EU_WITHDRAWALS, 'wID=' . $euwPostId, 'SSL'));
}

// Keep Done Withdrawals (Days): 0 (the default) keeps everything.
$euwStore->purge(EuWithdrawalCore::settingInt('EU_WITHDRAWAL_RETENTION_DAYS', 0, 0, 36500), gmdate('Y-m-d H:i:s'));

// Core's zen_href_link() hands back HTML-ready links (& already &amp;), so
// they're printed as they come; only our own values go through $euwH.
$euwSelf = zen_href_link(FILENAME_EU_WITHDRAWALS, '', 'SSL');
$euwId = (int)($_GET['wID'] ?? 0);
$euwRow = $euwId > 0 ? $euwStore->find($euwId) : null;
if ($euwId > 0 && $euwRow === null) {
    $messageStack->add(EU_WITHDRAWAL_ADMIN_ERR_NOT_FOUND, 'error');
}
if ($euwRow === null) {
    $euwList = $euwStore->search((string)($_GET['status'] ?? ''), (string)($_GET['q'] ?? ''), (int)($_GET['page'] ?? 1));
    $euwCounts = $euwStore->counts();
}
$euwOrderLink = static function (array $row) use ($euwH): string {
    if ((int)$row['matched'] === 1 && (int)$row['orders_id'] > 0) {
        return '<a href="' . zen_href_link(FILENAME_ORDERS, 'oID=' . (int)$row['orders_id'] . '&action=edit', 'NONSSL') . '">#' . (int)$row['orders_id'] . '</a>';
    }
    return $euwH(sprintf(EU_WITHDRAWAL_ADMIN_NOT_MATCHED, $row['order_entered']));
};
?>
<!DOCTYPE html>
<html <?= HTML_PARAMS ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
<style>
.euw-admin h2{font-size:1.2em;margin:24px 0 8px;padding-bottom:4px;border-bottom:1px solid #d8dee4}
.euw-admin .euw-counts a{margin-right:14px}
.euw-admin .euw-counts a.euw-current{font-weight:bold;text-decoration:underline}
.euw-admin .euw-search{margin:12px 0}
.euw-admin .euw-search input[type=search]{min-width:280px}
.euw-admin .euw-warn{color:#a40000;font-weight:bold}
.euw-admin dl.euw-facts{display:grid;grid-template-columns:max-content 1fr;gap:4px 16px;margin:0}
.euw-admin dl.euw-facts dt{font-weight:bold}
.euw-admin dl.euw-facts dd{margin:0}
.euw-admin .euw-actions{display:flex;flex-wrap:wrap;gap:24px;margin:18px 0}
.euw-admin .euw-actions form{min-width:260px}
.euw-admin textarea{width:100%;max-width:40em}
</style>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_ADMIN_HEAD', ['withdrawal' => $euwRow]); ?>
</head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<div class="container-fluid euw-admin">
<?php if ($euwRow === null) { ?>
  <h1><?= $euwH(EU_WITHDRAWAL_ADMIN_HEADING) ?></h1>
  <p><?= $euwH(EU_WITHDRAWAL_ADMIN_INTRO) ?></p>
<?php if (!EuWithdrawalCore::enabled()) { ?>
  <p class="euw-warn"><?= $euwH(EU_WITHDRAWAL_ADMIN_OFF) ?></p>
<?php } ?>
<?php if ($euwCounts['held'] > 0) { ?>
  <p class="euw-warn"><a href="<?= zen_href_link(FILENAME_EU_WITHDRAWALS, 'status=held', 'SSL') ?>"><?= $euwH(sprintf(EU_WITHDRAWAL_ADMIN_HELD_WARNING, $euwCounts['held'])) ?></a></p>
<?php } ?>

  <p class="euw-counts">
    <a href="<?= $euwSelf ?>"<?= $euwList['status'] === '' ? ' class="euw-current" aria-current="page"' : '' ?>><?= $euwH(EU_WITHDRAWAL_ADMIN_ALL) ?> (<?= array_sum($euwCounts) ?>)</a>
<?php foreach ($euwCounts as $euwKey => $euwN) { ?>
    <a href="<?= zen_href_link(FILENAME_EU_WITHDRAWALS, 'status=' . $euwKey, 'SSL') ?>"<?= $euwList['status'] === $euwKey ? ' class="euw-current" aria-current="page"' : '' ?>><?= $euwH($euwStatusLabel($euwKey)) ?> (<?= (int)$euwN ?>)</a>
<?php } ?>
  </p>

  <form class="euw-search form-inline" method="get" action="<?= $euwSelf ?>" role="search">
    <input type="hidden" name="cmd" value="<?= $euwH(FILENAME_EU_WITHDRAWALS) ?>">
    <input type="hidden" name="status" value="<?= $euwH($euwList['status']) ?>">
    <label for="euw-q"><?= $euwH(EU_WITHDRAWAL_ADMIN_SEARCH) ?></label>
    <input type="search" class="form-control" name="q" id="euw-q" value="<?= $euwH($euwList['query']) ?>">
    <button type="submit" class="btn btn-primary"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_SEARCH) ?></button>
    <a class="btn btn-default" href="<?= $euwSelf ?>"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_RESET) ?></a>
  </form>

<?php if ($euwList['rows'] === []) { ?>
  <p><?= $euwH(EU_WITHDRAWAL_ADMIN_NONE) ?></p>
<?php } else { ?>
  <table class="table table-striped table-condensed">
    <thead><tr>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_REF) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_SUBMITTED) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_CUSTOMER) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_ORDER) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_ITEMS) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_STATUS) ?></th>
      <th scope="col"><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_ACK) ?></th>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_HEAD'); ?>
      <th scope="col"><span class="sr-only"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_DETAILS) ?></span></th>
    </tr></thead>
    <tbody>
<?php foreach ($euwList['rows'] as $euwItem) {
    $euwItemRef = EuWithdrawalCore::reference((int)$euwItem['eu_withdrawals_id']);
    ?>
      <tr>
        <td><?= $euwH($euwItemRef) ?></td>
        <td><?= $euwH($euwItem['created_local']) ?></td>
        <td><?= $euwH($euwItem['name']) ?><br><small><?= $euwH($euwItem['email']) ?></small></td>
        <td><?= $euwOrderLink($euwItem) ?></td>
        <td><?= $euwH(EuWithdrawalCore::itemsText($euwItem)) ?></td>
        <td><?= $euwH($euwStatusLabel((string)$euwItem['status'])) ?></td>
        <td><?= $euwH($euwAckText($euwItem)) ?></td>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_ADMIN_LIST_ROW', ['withdrawal' => $euwItem]); ?>
        <td><a class="btn btn-default btn-xs" href="<?= zen_href_link(FILENAME_EU_WITHDRAWALS, 'wID=' . (int)$euwItem['eu_withdrawals_id'], 'SSL') ?>" aria-label="<?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_DETAILS . ' ' . $euwItemRef) ?>"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_DETAILS) ?></a></td>
      </tr>
<?php } ?>
    </tbody>
  </table>
<?php
    $euwFirst = ($euwList['page'] - 1) * EuWithdrawalStore::PER_PAGE + 1;
    $euwLast = min($euwList['total'], $euwList['page'] * EuWithdrawalStore::PER_PAGE);
    $euwKeep = ($euwList['status'] !== '' ? '&status=' . $euwList['status'] : '') . ($euwList['query'] !== '' ? '&q=' . urlencode($euwList['query']) : '');
    ?>
  <nav aria-label="<?= $euwH(EU_WITHDRAWAL_ADMIN_HEADING) ?>">
    <?= $euwH(sprintf(EU_WITHDRAWAL_ADMIN_SHOWING, $euwFirst, $euwLast, $euwList['total'])) ?>
<?php if ($euwList['page'] > 1) { ?>
    <a href="<?= zen_href_link(FILENAME_EU_WITHDRAWALS, 'page=' . ($euwList['page'] - 1) . $euwKeep, 'SSL') ?>"><?= $euwH(EU_WITHDRAWAL_ADMIN_PREVIOUS) ?></a>
<?php } ?>
<?php if ($euwList['page'] < $euwList['pages']) { ?>
    <a href="<?= zen_href_link(FILENAME_EU_WITHDRAWALS, 'page=' . ($euwList['page'] + 1) . $euwKeep, 'SSL') ?>"><?= $euwH(EU_WITHDRAWAL_ADMIN_NEXT) ?></a>
<?php } ?>
  </nav>
<?php } ?>

<?php } else {
    $euwRef = EuWithdrawalCore::reference((int)$euwRow['eu_withdrawals_id']);
    $euwHeld = $euwRow['status'] === 'held';
    $euwAckFailed = !$euwHeld && (string)$euwRow['ack_sent_utc'] === EuWithdrawalCore::NEVER;
    ?>
  <p><a href="<?= $euwSelf ?>">&larr; <?= $euwH(EU_WITHDRAWAL_ADMIN_BACK) ?></a></p>
  <h1><?= $euwH(sprintf(EU_WITHDRAWAL_ADMIN_DETAIL_HEADING, $euwRef)) ?></h1>

  <h2><?= $euwH(EU_WITHDRAWAL_ADMIN_STATEMENT) ?></h2>
  <dl class="euw-facts">
    <dt><?= $euwH(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_SUBMITTED')) ?></dt>
    <dd><?= $euwH($euwRow['created_local']) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_SUBMITTED_UTC) ?></dt>
    <dd><?= $euwH($euwRow['created_utc']) ?></dd>
    <dt><?= $euwH(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_NAME')) ?></dt>
    <dd><?= $euwH($euwRow['name']) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_EMAIL) ?></dt>
    <dd><a href="mailto:<?= $euwH(rawurlencode((string)$euwRow['email'])) ?>"><?= $euwH($euwRow['email']) ?></a></dd>
    <dt><?= $euwH(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_ORDER')) ?></dt>
    <dd><?= $euwOrderLink($euwRow) ?></dd>
    <dt><?= $euwH(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_ITEMS')) ?></dt>
    <dd><?= nl2br($euwH(EuWithdrawalCore::itemsText($euwRow))) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_CUSTOMER_ACCOUNT) ?></dt>
    <dd><?php if ((int)$euwRow['customers_id'] > 0) { ?><a href="<?= zen_href_link(FILENAME_CUSTOMERS, 'cID=' . (int)$euwRow['customers_id'] . '&action=edit', 'NONSSL') ?>">#<?= (int)$euwRow['customers_id'] ?></a><?php } else { ?><?= $euwH(EU_WITHDRAWAL_ADMIN_GUEST) ?><?php } ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_LANGUAGE) ?></dt>
    <dd><?= $euwH($euwRow['language']) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_IP) ?></dt>
    <dd><?= $euwH($euwRow['ip']) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_COUNTRY) ?></dt>
    <dd><?= $euwH($euwRow['country'] !== '' ? $euwRow['country'] : EU_WITHDRAWAL_ADMIN_UNKNOWN) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_STATUS) ?></dt>
    <dd><?= $euwH($euwStatusLabel((string)$euwRow['status'])) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_COL_ACK) ?></dt>
    <dd><?= $euwH($euwAckText($euwRow)) ?></dd>
    <dt><?= $euwH(EU_WITHDRAWAL_ADMIN_FIELD_ORDER_NOTE) ?></dt>
    <dd><?= $euwH((int)$euwRow['osh_id'] > 0 ? EU_WITHDRAWAL_ADMIN_ORDER_NOTE_DONE : EU_WITHDRAWAL_ADMIN_ORDER_NOTE_NONE) ?></dd>
  </dl>

  <div class="euw-actions">
<?php if ($euwHeld || $euwAckFailed) { ?>
    <?= zen_draw_form('euw_send_ack', FILENAME_EU_WITHDRAWALS, '', 'post') ?>
      <input type="hidden" name="action" value="send_ack">
      <input type="hidden" name="wID" value="<?= (int)$euwRow['eu_withdrawals_id'] ?>">
      <p><button type="submit" class="btn btn-primary"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_SEND_ACK) ?></button></p>
      <p class="help-block"><?= $euwH($euwHeld ? EU_WITHDRAWAL_ADMIN_SEND_ACK_HELP_HELD : EU_WITHDRAWAL_ADMIN_SEND_ACK_HELP_FAILED) ?></p>
    </form>
<?php } ?>
<?php if (!$euwHeld) { ?>
    <?= zen_draw_form('euw_status', FILENAME_EU_WITHDRAWALS, '', 'post') ?>
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="wID" value="<?= (int)$euwRow['eu_withdrawals_id'] ?>">
      <label for="euw-status"><?= $euwH(EU_WITHDRAWAL_ADMIN_SET_STATUS) ?></label>
      <select name="status" id="euw-status" class="form-control">
<?php foreach (['received', 'in_progress', 'done'] as $euwOption) { ?>
        <option value="<?= $euwH($euwOption) ?>"<?= $euwRow['status'] === $euwOption ? ' selected' : '' ?>><?= $euwH($euwStatusLabel($euwOption)) ?></option>
<?php } ?>
      </select>
      <p><button type="submit" class="btn btn-default"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_SAVE_STATUS) ?></button></p>
    </form>
<?php } ?>
    <?= zen_draw_form('euw_note', FILENAME_EU_WITHDRAWALS, '', 'post') ?>
      <input type="hidden" name="action" value="note">
      <input type="hidden" name="wID" value="<?= (int)$euwRow['eu_withdrawals_id'] ?>">
      <label for="euw-note"><?= $euwH(EU_WITHDRAWAL_ADMIN_NOTE) ?></label>
      <textarea name="admin_note" id="euw-note" rows="4" class="form-control"><?= $euwH($euwRow['admin_note']) ?></textarea>
      <p><button type="submit" class="btn btn-default"><?= $euwH(EU_WITHDRAWAL_ADMIN_BUTTON_SAVE_NOTE) ?></button></p>
    </form>
  </div>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_ADMIN_DETAIL', ['withdrawal' => $euwRow]); ?>
<?php } ?>
</div>
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
