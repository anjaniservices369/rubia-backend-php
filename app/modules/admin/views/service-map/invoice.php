<?php

use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $inv array */
/* @var $profile array */
/* @var $customer array|null */
/* @var $city string */

$money = function ($v) {
    return number_format((float) $v, 2);
};
$name = $customer ? $customer['name'] : '';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Invoice <?= Html::encode($inv['number']) ?></title>
<style>
 body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:32px;max-width:760px}
 h1{font-size:20px;margin:0 0 4px} .muted{color:#666;font-size:13px}
 table{border-collapse:collapse;width:100%;margin-top:18px} th,td{border:1px solid #bbb;padding:7px 9px;text-align:left;font-size:14px}
 td.r,th.r{text-align:right} .head{display:flex;justify-content:space-between;gap:24px;margin-bottom:16px}
 @media print{.noprint{display:none}}
</style></head>
<body>
<div class="noprint" style="margin-bottom:14px"><button onclick="window.print()">Print</button></div>
<div class="head">
  <div>
    <h1><?= Html::encode($profile['legal_name'] ?: $title) ?></h1>
    <div class="muted"><?= Html::encode($profile['address']) ?></div>
    <div class="muted">GSTIN: <?= Html::encode($profile['gstin'] ?: '-') ?> · PAN: <?= Html::encode($profile['pan'] ?: '-') ?> · State code: <?= Html::encode($profile['state_code'] ?: '-') ?></div>
  </div>
  <div style="text-align:right">
    <h1>TAX INVOICE</h1>
    <div><strong><?= Html::encode($inv['number']) ?></strong></div>
    <div class="muted">Date: <?= Html::encode(substr($inv['issued_on'], 0, 10)) ?></div>
    <div class="muted">Service: <?= Html::encode($title) ?></div>
  </div>
</div>
<div class="muted">Billed to: <?= Html::encode($name ?: 'Customer #' . (int) $inv['customer_id']) ?><?= $customer && $customer['phone'] ? ' · ' . Html::encode($customer['phone']) : '' ?>
 · Place of supply: <?= Html::encode($city ?: '-') ?> · Reference: <?= Html::encode($inv['source_type'] . ' #' . $inv['source_id']) ?></div>
<table>
  <thead><tr><th>Description</th><th>SAC</th><th class="r">Taxable value</th><th class="r">CGST</th><th class="r">SGST</th><th class="r">IGST</th><th class="r">Total</th></tr></thead>
  <tbody><tr>
    <td><?= Html::encode($title) ?> - ride / booking <?= Html::encode('#' . $inv['source_id']) ?></td><td><?= Html::encode($profile['sac_code']) ?></td>
    <td class="r"><?= $money($inv['taxable']) ?></td><td class="r"><?= $money($inv['cgst']) ?></td><td class="r"><?= $money($inv['sgst']) ?></td>
    <td class="r"><?= $money($inv['igst']) ?></td><td class="r"><strong><?= $money($inv['total']) ?></strong></td>
  </tr></tbody>
</table>
<p class="muted" style="margin-top:16px">Amounts in INR. Tax is included in the total. This is a computer generated invoice.</p>
</body></html>
