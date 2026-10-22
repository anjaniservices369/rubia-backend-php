<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\support\models\SolutionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Solutions');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="solution-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Solution'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'category_id',
            'title',
            'description:ntext',
            'state_id',
            //'type_id',
            //'created_on',
            //'updated_on',
            //'create_user_id',

            ['class' => 'kartik\grid\ActionColumn'],
        ],
    ]); ?>
</div>
