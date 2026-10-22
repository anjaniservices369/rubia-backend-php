
<?php 
if (!empty($model)) {?>

   <h4><?= $model->title?></h4>
   <p><?= $model->description?></p>
<?php } else {
    echo \Yii::t('app', 'No data found');
}?>