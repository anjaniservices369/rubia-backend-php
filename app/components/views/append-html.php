<?php
use \yii\widgets\ActiveForm;
use app\models\User;
use yii\helpers\Html;
?>


<div class="panel-body">
<style>
    .button-margins{

        margin-left: 2px;

    }
</style>

    <?php $form = ActiveForm::begin(['id' => 'states-form',]); ?>


        <?php
        echo '<div class="">';

        foreach ( $allowed as $key => $value ) {

            if ($key != $model->{$attribute}) {
                  echo Html::submitButton ( $value, array (
                    'name' => 'get-state',
                    'value' => $value,
                    'class' => 'btn btn-primary button-margins '
                ) );

            }
        }
        echo '</div>';
        ?>

    <?php ActiveForm::end(); ?>



</div>