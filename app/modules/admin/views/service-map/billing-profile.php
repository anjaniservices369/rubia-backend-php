<?php

use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $profile array */

$this->title = $title . ' - billing profile';
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index', 'module' => $module]];
$this->params['breadcrumbs'][] = 'Billing profile';
foreach (['success', 'error'] as $kind) {
    if ($msg = Yii::$app->session->getFlash($kind)) {
        echo '<div class="alert alert-' . ($kind === 'error' ? 'danger' : 'success') . '">' . Html::encode($msg) . '</div>';
    }
}
?>
<div class="card">
    <div class="card-header"><strong>Billing profile - <?= Html::encode($title) ?></strong></div>
    <div class="card-body">
        <p class="text-muted">
            This service issues its own tax invoices under this identity, with its own number series
            (<code><?= Html::encode($profile['invoice_prefix']) ?>/2026-27/000001</code>, restarting every financial year,
            April to March). Changes apply to invoices issued from now on; invoices already issued are not changed.
        </p>
        <?= Html::beginForm(['billing-profile', 'module' => $module], 'post', ['class' => 'row g-3']) ?>
        <div class="col-md-6"><label class="form-label">Legal name (on the invoice)</label>
            <?= Html::textInput('legal_name', $profile['legal_name'], ['class' => 'form-control', 'maxlength' => 160]) ?></div>
        <div class="col-md-3"><label class="form-label">GSTIN</label>
            <?= Html::textInput('gstin', $profile['gstin'], ['class' => 'form-control', 'maxlength' => 15, 'placeholder' => '36ABCDE1234F1Z5']) ?></div>
        <div class="col-md-3"><label class="form-label">PAN</label>
            <?= Html::textInput('pan', $profile['pan'], ['class' => 'form-control', 'maxlength' => 12]) ?></div>
        <div class="col-md-9"><label class="form-label">Registered address</label>
            <?= Html::textInput('address', $profile['address'], ['class' => 'form-control', 'maxlength' => 300]) ?></div>
        <div class="col-md-3"><label class="form-label">State code (GST)</label>
            <?= Html::textInput('state_code', $profile['state_code'], ['class' => 'form-control', 'maxlength' => 4, 'placeholder' => '36']) ?></div>
        <div class="col-md-3"><label class="form-label">Invoice prefix</label>
            <?= Html::textInput('invoice_prefix', $profile['invoice_prefix'], ['class' => 'form-control', 'maxlength' => 8, 'required' => true]) ?></div>
        <div class="col-md-3"><label class="form-label">SAC code</label>
            <?= Html::textInput('sac_code', $profile['sac_code'], ['class' => 'form-control', 'maxlength' => 10]) ?></div>
        <div class="col-12"><?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Invoices', ['invoices', 'module' => $module], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Tax report', ['tax-report', 'module' => $module], ['class' => 'btn btn-outline-secondary']) ?></div>
        <?= Html::endForm() ?>
    </div>
</div>
