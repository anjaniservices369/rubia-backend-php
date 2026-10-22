<?php

use app\models\User;

use yii\helpers\Html;
use kartik\form\ActiveForm;
use yii\jui\DatePicker;
/**
 * @var $this yii\web\View
 * @var $model \app\modules\admin\forms\UserForm
 * @var $form yii\widgets\ActiveForm
 */
?>

<div class="user-form card">
<div class="card-body">

 <style>

            /* Add this to your CSS file */
            .custom-btn {
                margin-right: 10px; /* Adjust margin as needed */
                margin-bottom: 10px; /* Adjust margin as needed */
            }

            .custom-btn-password {
                background-color: blue; /* Replace with your desired color name */
                border-color: blue;
                color: #ffffff; /* Text color */
            }

            .custom-btn-password:hover {
                background-color: lightblue; /* Replace with your desired hover color name */
                border-color: lightblue;
                color: #ffffff; /* Text color on hover */
            }

            .custom-btn-bank {
                background-color: green; /* Replace with your desired color name */
                border-color: green;
                color: #ffffff; /* Text color */
            }

            .custom-btn-bank:hover {
                background-color: lightgreen; /* Replace with your desired hover color name */
                border-color: lightgreen;
                color: #ffffff; /* Text color on hover */
            }

        </style>

        <div class="col-sm-12" style="margin-top: 15px">
            <?= Html::a('Driver PASSWORD UPDATE', ['user-password-update', 'id' => $model->id], ['class' => 'btn btn-primary custom-btn custom-btn-password']) ?>

            <?= Html::a('Driver BANK UPDATE', ['user-bank-update', 'id' => $model->id], ['class' => 'btn btn-primary custom-btn custom-btn-bank']) ?>

            <?= Html::a('Driver License UPDATE', ['user-license-update', 'id' => $model->id], ['class' => 'btn btn-primary custom-btn']) ?>

            <?= Html::a('Driver Pan UPDATE', ['user-pan-update', 'id' => $model->id], ['class' => 'btn btn-primary custom-btn']) ?>

            <?= Html::a('Driver Aadhar UPDATE', ['user-aadhar-update', 'id' => $model->id], ['class' => 'btn btn-primary custom-btn']) ?>
        </div>

    </div>
</div>

<?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>


	
<?= $form->field($model, 'first_name')->textInput(['maxlength' => true, 'id' => 'first_name']) ?>
<?= $form->field($model, 'last_name')->textInput(['maxlength' => true, 'id' => 'last_name']) ?>
		
		<?=$form->field($model, 'username', ['enableAjaxValidation' => true]); ?>
		
		<?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
		
		<?= $form->field($model, 'date_of_birth')->widget(yii\jui\DatePicker::class, [
          'dateFormat' => 'dd-MM-yyyy', // Format for the widget
          'options' => [
              'class' => 'form-control',
          ],
          'clientOptions' => [
              'dateFormat' => 'dd-MM-yyyy', // Format for the JavaScript datepicker
          ],
          'value' => $model->date_of_birth ? date('dd-MM-yyyy', strtotime($model->date_of_birth)) : null,
      ]) ?>

		<?= $form->field($model, 'user_role')->dropDownList(User::getRoles()) ?>
		
		<?= $form->field($model, 'status')->dropDownList(User::getStatusesList()) ?>

		<?= $form->field($model, 'city_id')->widget(\kartik\widgets\Select2::classname(), [
            'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->orderBy('id')->asArray()->all(), 'id', 'name'),
            'options' => ['placeholder' => Yii::t('app', 'Choose City')],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);  ?> 
		
	
	<div class="card-footer text-end">
		<?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => 'btn btn-primary']) ?>
	</div>

	
	<?php ActiveForm::end(); ?>
	</div>
</div>
