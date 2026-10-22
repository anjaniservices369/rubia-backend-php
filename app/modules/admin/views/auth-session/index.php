<?php
/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\AuthSessionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Login Sessions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-session-index">
    <p class="text-muted">
        Active app sessions. The session token itself is never shown here.
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'create_user_id',
                'label' => 'User',
                'value' => function ($model) {
                    $user = \app\models\User::findOne($model->create_user_id);
                    return $user ? ($user->username . ' (' . $user->user_role . ')') : 'user #' . $model->create_user_id . ' (removed)';
                },
            ],
            [
                'attribute' => 'type_id',
                'label' => 'Device',
                'value' => function ($model) {
                    return [1 => 'Android', 2 => 'iOS'][(int) $model->type_id] ?? '-';
                },
            ],
            [
                'label' => 'Push registered',
                'value' => function ($model) {
                    return $model->device_token ? 'yes' : 'no';
                },
            ],
            'created_on',
            'updated_on',
        ],
    ]) ?>
</div>