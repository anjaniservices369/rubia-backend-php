<?php
use yii\helpers\Url;


?>

<?php if(\Yii::$app->controller->id!='restaurant'){ ?>
<!-- START HEADER -->
<?= $this->render('/includes/index_header')?>
<!-- container -->
<?php }elseif (\Yii::$app->controller->id=='restaurant'  && \Yii::$app->controller->action->id=='details' ){?>


    <!-- START HEADER -->
    <?= $this->render('/includes/restaurant_list_header')  ?>


<?php }else{?>

    <?= $this->render('/includes/common_header')  ?>


<?php }?>