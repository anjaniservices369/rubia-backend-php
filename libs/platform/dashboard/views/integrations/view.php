<?php

use app\modules\admin\models\Integrations;
use app\modules\admin\models\SettingGuide;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $id string */
/* @var $def array */

$this->title = $def['name'];
$this->params['breadcrumbs'][] = ['label' => 'Integrations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$st = Integrations::status($id);
$badge = ['ready' => ['success', 'Ready'], 'partial' => ['warning', 'Needs more keys'], 'missing' => ['secondary', 'Not set up']][$st['state']];
$webhook = isset($def['webhook']) ? rtrim(Yii::$app->request->hostInfo, '/') . $def['webhook'] : null;
?>
<div class="integrations-view">
    <p><?= Html::a('&larr; All integrations', ['index']) ?></p>
    <h3><span class="me-1"><?= $def['icon'] ?></span><?= Html::encode($def['name']) ?> <small class="text-muted fs-6">for <?= Html::encode(Integrations::serviceName()) ?></small>
        <?php if (empty($def['plain'])) : ?><span class="badge text-bg-<?= $badge[0] ?> fs-6"><?= $badge[1] ?></span><?php endif; ?>
        <?php if ($st['mode']) : ?><span class="badge text-bg-<?= $st['mode'] === 'live' ? 'danger' : 'info' ?> fs-6"><?= $st['mode'] ?></span><?php endif; ?>
    </h3>
    <p class="text-muted"><?= Html::encode($def['purpose']) ?></p>

    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $msg) : ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : Html::encode($type) ?>"><?= Html::encode(is_array($msg) ? implode(' ', $msg) : $msg) ?></div>
    <?php endforeach; ?>

    <div class="rb-split">
    <div class="rb-main">
    <div class="card"><div class="card-body">
        <?= Html::beginForm(['view', 'id' => $id], 'post', ['autocomplete' => 'off']) ?>
        <?php $cols = count($def['fields']) > 3 ? 'col-md-6' : 'col-12'; ?>
        <div class="row">
        <?php foreach ($def['fields'] as $key => $required) :
            $current = Integrations::value($key);
            $secret = SettingGuide::isSecret($key); ?>
            <div class="<?= $cols ?> mb-3">
                <label class="form-label" for="s-<?= Html::encode($key) ?>">
                    <code><?= Html::encode($key) ?></code>
                    <?= $required ? '' : '<span class="text-muted small">(optional)</span>' ?>
                    <?= SettingGuide::badge($key, $current !== '') ?>
                </label>
                <?php if (in_array($key, $def['bool'] ?? [], true)) : ?>
                    <?= Html::hiddenInput('s[' . $key . ']', '0') ?>
                    <div class="form-check"><?= Html::checkbox('s[' . $key . ']', $current === '1', ['value' => '1', 'id' => 's-' . $key, 'class' => 'form-check-input']) ?>
                        <label class="form-check-label" for="s-<?= Html::encode($key) ?>">On</label></div>
                <?php elseif (in_array($key, $def['multiline'] ?? [], true)) : ?>
                    <?= Html::textarea('s[' . $key . ']', $current, ['id' => 's-' . $key, 'class' => 'form-control', 'rows' => 3, 'maxlength' => 500]) ?>
                <?php elseif ($secret) : ?>
                    <?= Html::passwordInput('s[' . $key . ']', '', ['id' => 's-' . $key, 'class' => 'form-control', 'autocomplete' => 'new-password',
                        'placeholder' => $current !== '' ? '•••••••• saved - type to replace' : 'paste the value here']) ?>
                <?php else : ?>
                    <?= Html::textInput('s[' . $key . ']', $current, ['id' => 's-' . $key, 'class' => 'form-control', 'autocomplete' => 'off']) ?>
                <?php endif; ?>
                <div class="form-text"><?= SettingGuide::note($key) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <?php if (!empty($def['testable'])) : ?>
            <button type="button" class="btn btn-outline-secondary" id="int-test" data-url="<?= Html::encode(Url::to(['test', 'id' => $id])) ?>">Test connection</button>
            <span id="int-result" class="ms-2"></span>
        <?php endif; ?>
        <?= Html::endForm() ?>
    </div></div>

    </div>
    <div class="rb-side">
    <?php if ($webhook) : ?>
        <div class="card mb-3"><div class="card-body">
            <h6>Webhook address</h6>
            <p><code><?= Html::encode($webhook) ?></code></p>
            <p class="small text-muted mb-0"><?= Html::encode($def['webhookNote']) ?></p>
        </div></div>
    <?php endif; ?>

    <?php if (empty($def['plain'])) : ?>
    <div class="card mb-3"><div class="card-body small">
        <h6>Where the keys come from</h6>
        <p class="mb-1">Get them here: <a href="<?= Html::encode($def['docs']) ?>" target="_blank" rel="noopener"><?= Html::encode($def['docs']) ?></a></p>
        <p class="mb-0">These settings belong to this service only. The server reads each box from this page first. If a box is empty here, it falls back to the server environment variable named like the box in capitals, with the service prefix first (for example <code><?= Html::encode(strtoupper(Yii::$app->params['serviceEntry']['service'] ?? 'service')) ?>_</code> + the name, which applies to this service only):
            <?= implode(', ', array_map(function ($k) { return '<code>' . Html::encode(strtoupper($k)) . '</code>'; }, array_keys($def['fields']))) ?>.</p>
    </div></div>
    <?php endif; ?>
    </div>
    </div>
</div>
<?php if (!empty($def['testable'])) :
    $csrfParam = Yii::$app->request->csrfParam;
    $csrf = Yii::$app->request->csrfToken;
    $this->registerJs(<<<JS
document.getElementById('int-test').addEventListener('click', function () {
    var out = document.getElementById('int-result'), btn = this;
    out.className = 'ms-2 text-muted'; out.textContent = 'Testing...'; btn.disabled = true;
    var body = new URLSearchParams(); body.append('$csrfParam', '$csrf');
    fetch(btn.dataset.url, {method: 'POST', body: body, headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
        .then(function (r) { return r.json(); })
        .then(function (j) { out.className = 'ms-2 ' + (j.ok ? 'text-success' : 'text-danger'); out.textContent = j.message; })
        .catch(function () { out.className = 'ms-2 text-danger'; out.textContent = 'The test could not be run.'; })
        .finally(function () { btn.disabled = false; });
});
JS);
endif; ?>
