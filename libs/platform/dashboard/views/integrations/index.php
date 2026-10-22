<?php

use app\modules\admin\models\Integrations;
use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = 'Integrations';
$this->params['breadcrumbs'][] = $this->title;

$byCategory = [];
foreach (Integrations::visible() as $id => $def) {
    $byCategory[$def['category']][$id] = $def;
}
$stateBadge = [
    'ready' => ['success', 'Ready'],
    'partial' => ['warning', 'Needs more keys'],
    'missing' => ['secondary', 'Not set up'],
];
?>
<div class="integrations-index">
    <p class="mb-2"><span class="badge text-bg-primary fs-6"><?= Html::encode(Integrations::serviceName()) ?></span></p>
    <p class="text-muted">Every third-party service this system uses, each on its own page: its keys, where to get them, a connection test. Boxes marked
        <span class="badge text-bg-danger">&#128274; Protected</span> are passwords, secret keys and tokens: they are saved encrypted, hidden after saving and never shown again.
        The settings on these pages belong to <strong><?= Html::encode(Integrations::serviceName()) ?></strong> only: every service of the system keeps its own keys, in its own database, and cannot see the keys of another.</p>

    <div class="rb-cards">
    <?php foreach ($byCategory as $category => $items) : ?>
        <?php foreach ($items as $id => $def) :
            $st = Integrations::status($id);
            [$cls, $label] = $stateBadge[$st['state']]; ?>
            <div class="card"><div class="card-body d-flex flex-column">
                <div class="rb-card-kicker"><?= Html::encode($category) ?></div>
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <h6 class="mb-1"><span class="me-1"><?= $def['icon'] ?></span><?= Html::encode($def['name']) ?></h6>
                    <span class="text-nowrap">
                        <?php if (empty($def['plain'])) : ?><span class="badge text-bg-<?= $cls ?>"><?= $label ?></span><?php endif; ?>
                        <?php if ($st['mode']) : ?><span class="badge text-bg-<?= $st['mode'] === 'live' ? 'danger' : 'info' ?>"><?= $st['mode'] ?></span><?php endif; ?>
                    </span>
                </div>
                <p class="small text-muted flex-grow-1 mt-2" title="<?= Html::encode($def['purpose']) ?>"><span class="rb-clamp"><?= Html::encode($def['purpose']) ?></span></p>
                <?= Html::a('Open', ['view', 'id' => $id], ['class' => 'btn btn-sm btn-outline-primary align-self-start']) ?>
            </div></div>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </div>
</div>
