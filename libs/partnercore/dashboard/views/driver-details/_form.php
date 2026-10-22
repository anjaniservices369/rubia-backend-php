<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\widgets\FileInput;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverDetails */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="driver-details-form">

    <?php $form = ActiveForm::begin([
        'id' => 'login-form-inline',
        'type' => ActiveForm::TYPE_VERTICAL,
        'tooltipStyleFeedback' => true, // shows tooltip styled validation error feedback
        'fieldConfig' => ['options' => ['class' => 'form-group col-xs-6 col-sm-6 col-md-6 col-lg-12']], // spacing field groups
        'formConfig' => ['showErrors' => true],
        // set style for proper tooltips error display
    ]); ?>

    <?= $form->errorSummary($model); ?>
    <div class="row">

        <div class='col-lg-6 '> <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

        </div>
        <div class='col-lg-6'> <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']);  ?> </div>

        <div class="card">
            <div class="row">

                <div class='col-lg-6'> <?= $form->field($model, 'user_id')->widget(\kartik\widgets\Select2::classname(), [
                                            'data' => \yii\helpers\ArrayHelper::map(\app\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
                                            'options' => ['placeholder' => Yii::t('app', 'Choose User'), 'disabled' => true],
                                            'pluginOptions' => [
                                                'allowClear' => true
                                            ],
                                        ]);  ?> </div>

                <div class='col-lg-6'> <?= $form->field($model, 'vehical_id')->widget(\kartik\widgets\Select2::classname(), [
                                            'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Vehicals::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
                                            'options' => ['placeholder' => Yii::t('app', 'Choose Vehicles ')],
                                            'pluginOptions' => [
                                                'allowClear' => true
                                            ],
                                        ]);  ?> </div>

                <div class='col-lg-6'> <?= $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'Address'])  ?> </div>

            </div>
        </div>
        <div class="card">

            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">User Detais</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'first_name')->textInput(['maxlength' => true, 'placeholder' => 'First Name'])  ?> </div>
                <div class='col-lg-6'> <?= $form->field($model, 'last_name')->textInput(['maxlength' => true, 'placeholder' => 'Last Name'])  ?> </div>
                <div class='col-lg-6'> <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'E-mail'])  ?> </div>
                <div class='col-lg-6'> <?= $form->field($model, 'gender')->textInput(['maxlength' => true, 'placeholder' => 'Gender'])  ?> </div>
                <div class='col-lg-6'> <?= $form->field($model, 'date_of_birth')->textInput(['maxlength' => true, 'placeholder' => 'Date Of Birth'])  ?> </div>





            </div>
        </div>
        <div class="card">

            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">Driving License</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'license_no')->textInput(['maxlength' => true, 'placeholder' => 'License No'])  ?> </div>
                <div class='col-lg-6'> </div>

                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'proof_of_license')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'proof_of_license', 'initialPreview' => [
                                                    $model->proof_of_license
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ]);


                                        ?></div>

                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'proof_of_license_back')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'proof_of_license_back', 'initialPreview' => [
                                                    $model->proof_of_license_back
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ]);


                                        ?></div>

            </div>
        </div>

        <div class="card">
            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">Vehical Registration</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'rc_number')->textInput(['maxlength' => true, 'placeholder' => 'Rc Number'])  ?> </div>
                <div class='col-lg-6'> <?= $form->field($model, 'vehical_number')->textInput(['maxlength' => true, 'placeholder' => 'Vehical Number'])  ?> </div>
                <div class='col-lg-12'> <?= $form->field($model, 'model_name')->textInput(['maxlength' => true, 'placeholder' => 'Model Name'])  ?> </div>
                <div class='col-lg-6'>
                    <?php

                    echo $form->field($model, 'rc_proof')->widget(FileInput::classname(), [
                        'options' => ['multiple' => false, 'accept' => 'image/*'],
                        'pluginOptions' => [
                            'previewFileType' => 'proof_of_license_back', 'initialPreview' => [
                                $model->proof_of_license_back
                            ],
                            'initialPreviewAsData' => true,

                            'overwriteInitial' => true,

                            'showUpload' => false,
                        ]
                    ]);


                    ?> </div>
                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'rc_proof_back')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'proof_of_license_back', 'initialPreview' => [
                                                    $model->proof_of_license_back
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ]);


                                        ?> </div>

            </div>
        </div>

        <div class="card">
            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">Chassis Details</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'chassis_number')->textInput(['maxlength' => true, 'placeholder' => 'Chassis Nmer'])  ?> </div>
                <div class='col-lg-6'>
                    <?php

                    echo $form->field($model, 'chassis_image')->widget(FileInput::classname(), [
                        'options' => ['multiple' => false, 'accept' => 'image/*'],
                        'pluginOptions' => [
                            'previewFileType' => 'proof_of_license_back', 'initialPreview' => [
                                $model->chassis_image
                            ],
                            'initialPreviewAsData' => true,

                            'overwriteInitial' => true,

                            'showUpload' => false,
                        ]
                    ]);


                    ?> </div>
                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'rc_proof_back')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'proof_of_license_back', 'initialPreview' => [
                                                    $model->proof_of_license_back
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ]);


                                        ?> </div>

            </div>
        </div>
        <div class="card">
            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">Aadhar Detail</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'adhaar_number')->textInput(['maxlength' => true, 'placeholder' => 'Aadhar Number'])->label('Aadhaar Number')  ?> </div>
                <div class='col-lg-6'> </div>

                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'adhaar_front')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'adhaar_front', 'initialPreview' => [
                                                    $model->adhaar_front
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ]);


                                        ?> </div>

                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'adhaar_back')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'adhaar_back', 'initialPreview' => [
                                                    $model->adhaar_back
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ])->label('Aadhar Back');


                                        ?> </div>
            </div>
        </div>

        <div class="card">
            <div class="row">
                <div class="card-heading">
                    <h5 class="mb-2">Pan Detail</h5>
                </div>
                <div class='col-lg-6'> <?= $form->field($model, 'pan_number')->textInput(['maxlength' => true, 'placeholder' => 'Pan Number'])->label('PAN Number')  ?> </div>

                <div class='col-lg-6'> <?php

                                        echo $form->field($model, 'pan_front')->widget(FileInput::classname(), [
                                            'options' => ['multiple' => false, 'accept' => 'image/*'],
                                            'pluginOptions' => [
                                                'previewFileType' => 'pan_front', 'initialPreview' => [
                                                    $model->pan_front
                                                ],
                                                'initialPreviewAsData' => true,

                                                'overwriteInitial' => true,

                                                'showUpload' => false,
                                            ]
                                        ])->label('PAN Front');


                                        ?> </div>


            </div>
        </div>


        <div class='col-lg-6'> <?= $form->field($model, 'vehical_speed')->dropDownList(['Low speed' => 'Low speed', 'High speed' => 'High speed'])  ?> </div>
        <div class='col-lg-6'> <?= $form->field($model, 'vehical_owner')->dropDownList(['Own' => 'Own', 'Rented' => 'Rented'])->label('Ownership')  ?> </div>


        <div class='col-lg-6'> <?= $form->field($model, 'status')->dropDownList($model->getStateOptions())  ?> </div>

    </div> <?php if ($model->isNewRecord) { ?><?php } ?> <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>