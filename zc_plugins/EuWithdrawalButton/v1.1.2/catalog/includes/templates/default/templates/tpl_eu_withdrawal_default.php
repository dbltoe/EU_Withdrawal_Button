<?php
/**
 * EU Withdrawal Button -- the withdrawal page.
 *
 * Built so it needs no template edits; copy it into your template's
 * templates/ folder to change it. The Confirm Withdrawal button's words are
 * fixed by law (Article 11a(3)): change its look, not its label.
 *
 * A copy made before v1.1.0 lacks the NOTIFY_EU_WITHDRAWAL_TPL_* points below,
 * where an add-on (EU Withdrawal Button Pro) draws its fields; copy them over.
 *
 * Contrast: white on #0b3d91 is 10.04:1, on the #072a66 hover 13.71:1, and
 * #0b3d91 on white is 10.04:1 (all AAA). Hover deepens the shadow only.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */
$euwE = static function ($s): string {
    return EuWithdrawalCore::esc((string)$s);
};
$euwAudience = trim(EuWithdrawalCore::setting('EU_WITHDRAWAL_AUDIENCE_NOTE', ''));
$euwExtra = isset($euwExtra) && is_array($euwExtra) ? $euwExtra : [];
$euwItemsShown = EuWithdrawalCore::combineItems($euwExtra['items_lines'] ?? [], (string)$euwForm['items']);
?>
<style>
#euWithdrawal .euw-field{margin:0 0 14px}
#euWithdrawal .euw-field label,#euWithdrawal .euw-legend{display:block;font-weight:700;margin:0 0 4px}
#euWithdrawal input[type=text],#euWithdrawal input[type=email],#euWithdrawal textarea{width:100%;max-width:32em;box-sizing:border-box;padding:6px}
#euWithdrawal fieldset{border:0;margin:0 0 14px;padding:0}
#euWithdrawal fieldset label{display:block;margin:2px 0;font-weight:400}
#euWithdrawal .euw-errors{border-left:4px solid #b00020;padding:6px 12px;margin:0 0 14px}
#euWithdrawal table.euw-review th{text-align:left;vertical-align:top;padding:4px 16px 4px 0}
#euWithdrawal table.euw-review td{vertical-align:top;padding:4px 0}
#euWithdrawal .euw-submit{display:inline-block;background:#0b3d91;color:#fff;font-weight:700;font-size:1rem;padding:.5em 1.1em;border:0;border-radius:4px;cursor:pointer}
#euWithdrawal .euw-submit:hover,#euWithdrawal .euw-submit:focus{background:#072a66;box-shadow:0 2px 6px rgba(0,0,0,.35)}
#euWithdrawal .euw-secondary{display:inline-block;background:#fff;color:#0b3d91;border:2px solid #0b3d91;font-weight:700;font-size:1rem;padding:.4em 1em;border-radius:4px;cursor:pointer}
#euWithdrawal .euw-secondary:hover,#euWithdrawal .euw-secondary:focus{box-shadow:0 2px 6px rgba(0,0,0,.35)}
#euWithdrawal .euw-submit:focus-visible,#euWithdrawal .euw-secondary:focus-visible{outline:3px solid #ffbf47;outline-offset:2px}
#euWithdrawal .euw-note{font-size:.95em;margin:14px 0}
#euWithdrawal .euw-hp{position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden}
</style>
<div class="centerColumn" id="euWithdrawal">
    <h1 id="euWithdrawalHeading"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_PAGE_TITLE')) ?></h1>

<?php if ($euwView === 'done' && is_array($euwDone)) { ?>
    <p><strong><?= $euwE(sprintf(EuWithdrawalCore::text('EU_WITHDRAWAL_DONE'), EuWithdrawalCore::reference((int)$euwDone['eu_withdrawals_id']), $euwDone['created_local'])) ?></strong></p>
    <p><?= $euwE(sprintf(EuWithdrawalCore::text($euwDone['status'] === 'held' ? 'EU_WITHDRAWAL_DONE_HELD' : 'EU_WITHDRAWAL_DONE_EMAILED'), $euwDone['email'])) ?></p>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_TPL_DONE', ['withdrawal' => $euwDone]); ?>

<?php } elseif ($euwView === 'review') { ?>
    <p><?= $euwE(sprintf(EuWithdrawalCore::text('EU_WITHDRAWAL_REVIEW_INTRO'), $euwConfirmLabel)) ?></p>
    <table class="euw-review">
        <tr><th scope="row"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_NAME')) ?></th><td><?= $euwE($euwForm['name']) ?></td></tr>
        <tr><th scope="row"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_ORDER')) ?></th><td><?= $euwForm['orders_id'] > 0 ? '#' . (int)$euwForm['orders_id'] : $euwE($euwForm['order']) ?></td></tr>
        <tr><th scope="row"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_ITEMS')) ?></th><td><?= $euwItemsShown !== '' ? nl2br($euwE($euwItemsShown)) : $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_WHOLE_ORDER')) ?></td></tr>
        <tr><th scope="row"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_FIELD_EMAIL')) ?></th><td><?= $euwE($euwForm['email']) ?></td></tr>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_TPL_REVIEW_ROWS', ['form' => $euwForm, 'extra' => $euwExtra]); ?>
    </table>
    <p class="euw-note"><?= sprintf($euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_PRIVACY')), '<a href="' . zen_href_link(FILENAME_PRIVACY, '', 'SSL') . '">' . $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_PRIVACY_LINK')) . '</a>') ?></p>
    <?= zen_draw_form('eu_withdrawal_confirm', $euwFormAction, 'post') ?>
        <?= zen_draw_hidden_field('action', 'confirm') ?>
        <p><button type="submit" class="euw-submit"><?= $euwE($euwConfirmLabel) ?></button></p>
    </form>
    <?= zen_draw_form('eu_withdrawal_change', $euwFormAction, 'post') ?>
        <?= zen_draw_hidden_field('action', 'change') ?>
        <?= zen_draw_hidden_field('euw_name', $euwForm['name']) ?>
        <?= zen_draw_hidden_field('euw_email', $euwForm['email']) ?>
        <?= zen_draw_hidden_field('euw_order', $euwForm['order']) ?>
        <?= zen_draw_hidden_field('euw_orders_id', (string)$euwForm['orders_id']) ?>
        <?= zen_draw_hidden_field('euw_items', $euwForm['items']) ?>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_TPL_CHANGE_FIELDS', ['form' => $euwForm, 'extra' => $euwExtra]); ?>
        <p><button type="submit" class="euw-secondary"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_BUTTON_CHANGE')) ?></button></p>
    </form>

<?php } else { ?>
    <p><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_PAGE_INTRO')) ?></p>
<?php if ($euwAudience !== '') { ?>
    <p class="euw-note"><?= $euwE($euwAudience) ?></p>
<?php } ?>
<?php if ($euwErrors !== []) { ?>
    <div class="euw-errors" role="alert">
<?php foreach ($euwErrors as $euwError) { ?>
        <p><?= $euwE($euwError) ?></p>
<?php } ?>
    </div>
<?php } ?>
    <?= zen_draw_form('eu_withdrawal', $euwFormAction, 'post') ?>
        <?= zen_draw_hidden_field('action', 'review') ?>
        <div class="euw-field">
            <label for="euw-name"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_NAME')) ?></label>
            <input type="text" id="euw-name" name="euw_name" value="<?= $euwE($euwForm['name']) ?>" maxlength="128" autocomplete="name" required>
        </div>
<?php if ($euwOrders !== []) { ?>
        <fieldset>
            <legend class="euw-legend"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_ORDERS')) ?></legend>
<?php foreach ($euwOrders as $euwOrderId => $euwOrder) { ?>
            <label><input type="radio" name="euw_orders_id" value="<?= (int)$euwOrderId ?>"<?= $euwForm['orders_id'] === (int)$euwOrderId ? ' checked' : '' ?>> <?= $euwE(sprintf(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_ORDER_LINE'), (int)$euwOrderId, zen_date_short($euwOrder['date_purchased']), $currencies->format($euwOrder['order_total'], true, $euwOrder['currency'], $euwOrder['currency_value']))) ?></label>
<?php } ?>
            <label><input type="radio" name="euw_orders_id" value="0"<?= $euwForm['orders_id'] === 0 ? ' checked' : '' ?>> <?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_ORDER_OTHER')) ?></label>
        </fieldset>
<?php } ?>
        <div class="euw-field">
            <label for="euw-order"><?= $euwE(EuWithdrawalCore::text($euwOrders !== [] ? 'EU_WITHDRAWAL_INPUT_ORDER_IF_NOT_LISTED' : 'EU_WITHDRAWAL_INPUT_ORDER')) ?></label>
            <input type="text" id="euw-order" name="euw_order" value="<?= $euwE($euwForm['order']) ?>" maxlength="64" inputmode="numeric">
        </div>
        <div class="euw-field">
            <label for="euw-email"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_EMAIL')) ?></label>
            <input type="email" id="euw-email" name="euw_email" value="<?= $euwE($euwForm['email']) ?>" maxlength="96" autocomplete="email" required>
        </div>
<?php EuWithdrawalCore::notify('NOTIFY_EU_WITHDRAWAL_TPL_FORM_FIELDS', ['form' => $euwForm, 'orders' => $euwOrders, 'extra' => $euwExtra]); ?>
        <div class="euw-field">
            <label for="euw-items"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_INPUT_ITEMS')) ?></label>
            <textarea id="euw-items" name="euw_items" rows="3" maxlength="2000"><?= $euwE($euwForm['items']) ?></textarea>
        </div>
        <div class="euw-hp" aria-hidden="true"><input type="text" name="<?= $euwE($euwAntiSpamField) ?>" value="" tabindex="-1" autocomplete="off"></div>
        <p><button type="submit" class="euw-submit"><?= $euwE(EuWithdrawalCore::text('EU_WITHDRAWAL_BUTTON_REVIEW')) ?></button></p>
    </form>
<?php } ?>
</div>
