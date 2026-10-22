<?php
/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Vehicals */
$this->title = 'Update Vehicle: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Vehicles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<?= $this->render('_form', ['model' => $model]) ?>
