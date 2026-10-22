<?php

namespace app\modules\media\models;

use Yii;
use app\components\BaseActiveRecord;
use app\models\User;
use yii\helpers\Html;
use yii\imagine\Image;

/**
 * This is the model class for table "media".
 *
 * @property int $id
 * @property int $model_id
 * @property string $model_type
 * @property string $size
 * @property string $file_name
 * @property string $original_name
 * @property string $extension
 * @property int $state_id
 * @property int $type_id
 * @property string $created_on
 * @property string $updated_on
 * @property int $create_user_id
 *
 * @property User $createUser
 * @property User $createUser0
 */
class Media extends BaseActiveRecord {
	
	/**
	 * @inheritdoc
	 */
	public $zip_file;
	public static function tableName() {
		return 'media';
	}
	
	/**
	 * @inheritdoc
	 */
	public function scenarios() {
		$scenarios = parent::scenarios ();
		$scenarios ['add'] = [ 
				'model_id',
				'file_name',
				'model_type',
				'size',
				'original_name',
				'extension' 
		
		];
		
		return $scenarios;
	}
	public function rules() {
		return [ 
				[ 
						[ 
								'model_id',
								// 'file_name',
								'create_user_id' 
						],
						'required' 
				],
				[ 
						[ 
								'model_id',
								'state_id',
								'type_id',
								'create_user_id' 
						],
						'integer' 
				],
				// [ 
				// 		[ 
				// 				'file_name' 
						
				// 		],
				// 		'required',
				// 		'on' => 'add' 
				// ],
				[ 
						[ 
								'created_on',
								'updated_on',
								'size',
								'alt',
								'title',
								'thumb_file',
								'zip_file' 
						],
						'safe' 
				],
				[ 
						[ 
								'model_type',
								'original_name',
								'extension' 
						],
						'string',
						'max' => 255 
				],
				[ 
						[ 
								'create_user_id' 
						],
						'exist',
						'skipOnError' => true,
						'targetClass' => User::className (),
						'targetAttribute' => [ 
								'create_user_id' => 'id' 
						] 
				] 
		
		];
	}
	public function beforeValidate() {
		if ($this->isNewRecord) {
			if (isset ( $this->created_on ))
				$this->created_on = date ( "Y-m-d H:i:s" );
			
			$this->create_user_id = \yii::$app->user->id;
		} else {
			if (isset ( $this->updated_on ))
				$this->updated_on = date ( "Y-m-d H:i:s" );
		}
		
		return parent::beforeValidate ();
	}
	
	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [ 
				'id' => Yii::t ( 'app', 'ID' ),
				'model_id' => Yii::t ( 'app', 'Model ID' ),
				'model_type' => Yii::t ( 'app', 'Model Type' ),
				'size' => Yii::t ( 'app', 'Size' ),
				'file_name' => Yii::t ( 'app', 'File Name' ),
				'original_name' => Yii::t ( 'app', 'Original Name' ),
				'extension' => Yii::t ( 'app', 'Extension' ),
				'state_id' => Yii::t ( 'app', 'State ID' ),
				'type_id' => Yii::t ( 'app', 'Type ID' ),
				'created_on' => Yii::t ( 'app', 'Created On' ),
				'updated_on' => Yii::t ( 'app', 'Updated On' ),
				'create_user_id' => Yii::t ( 'app', 'Create User ID' ) 
		];
	}
	
	/**
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreateUser() {
		return $this->hasOne ( User::className (), [ 
				'id' => 'create_user_id' 
		] );
	}
	
	/**
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreateUser0() {
		return $this->hasOne ( User::className (), [ 
				'id' => 'create_user_id' 
		] );
	}
	public function getZipFile($zipFolder, $name, $model, $alt) {
		if (! empty ( $name )) {
			
			if (is_dir ( UPLOAD_PATH . '/' . $zipFolder )) {
				$originFile = UPLOAD_PATH . '/' . $zipFolder . '/' . $name;
				if (file_exists ( $originFile )) {
					$media = Media::find ()->where ( [ 
							'model_id' => $model->id,
							'model_type' => get_class ( $model ) 
					] )->one ();
					if (empty ( $media )) {
						$media = new Media ();
					}
					$media->model_id = $model->id;
					$media->model_type = get_class ( $model );
					
					$ext = pathinfo ( $name, PATHINFO_EXTENSION );
					
					$thumbDir = UPLOAD_PATH . '/' . 'thumbnail/';
					if (! file_exists ( $thumbDir )) {
						mkdir ( $thumbDir, 0777, true );
					}
					$time = time ();
					$fileName = $name . '-' . $time . '.' . $ext;
					
					// $media->size =getimagesize($originFile);
					
					$thumbnFile = $thumbDir . $name . '_' . time () . '-thumb' . '_200x200' . '.' . $ext;
					// Generate a thumbnail image
					Image::thumbnail ( $originFile, 200, 200 )->save ( $thumbnFile, [ 
							'quality' => 80 
					] );
					rename ( $originFile, UPLOAD_PATH . '/' . $fileName );
					$media->extension = $ext;
					$media->file_name = $fileName;
					
					$media->alt = $alt;
					$media->thumb_file = $thumbnFile;
					$media->original_name = $name;
					$media->title = isset ( $model->title ) ? $model->title : 'Not set';
					$media->save ();
				}
			}
		}
	}

	public function bannerImage($options = [], $default = "user.png")
    {
        
      // var_dump( '/uploads/' . $this->file_name); exit;
        if (!empty ($this->file_name) ) {
            $file = [
                '/uploads/' . $this->file_name
            ];
        } else {
            $file = \yii::$app->urlManager->createAbsoluteUrl('themes/img/' . $default);
        }

        if (empty ($options)) {
            $options = [
                'class' => 'img-responsive'
            ];
        }

        return Html::img($file, $options);
    }
	public function getMultipleZipFile($zipFolder, $file, $model, $alt) {
		if (! empty ( $file )) {
			
			if (is_dir ( UPLOAD_PATH . '/' . $zipFolder )) {
				
				Media::deleteRelatedAll ( [ 
						'model_id' => $model->id,
						'model_type' => get_class ( $model ) 
				] );
				
				$names = explode ( ',', $file );
				if (! empty ( $names )) {
					foreach ( $names as $key => $name ) {
						$originFile = UPLOAD_PATH . '/' . $zipFolder . '/' . $name;
						if (file_exists ( $originFile )) {
							
							$alts = explode ( ',', $alt );
							$media = new Media ();
							$media->model_id = $model->id;
							$media->model_type = get_class ( $model );
							$ext = pathinfo ( $name, PATHINFO_EXTENSION );
							$thumbDir = UPLOAD_PATH . '/' . 'thumbnail/';
							if (! file_exists ( $thumbDir )) {
								mkdir ( $thumbDir, 0777, true );
							}
							$time = time ();
							$fileName = $name . '-' . $time . '.' . $ext;
							
							// $media->size =getimagesize($originFile);
							
							$thumbnFile = $thumbDir . $name . '_' . time () . '-thumb' . '_200x200' . '.' . $ext;
							// Generate a thumbnail image
							Image::thumbnail ( $originFile, 200, 200 )->save ( $thumbnFile, [ 
									'quality' => 80 
							] );
							rename ( $originFile, UPLOAD_PATH . '/' . $fileName );
							$media->extension = $ext;
							$media->file_name = $fileName;
							
							$media->alt = isset ( $alts [$key] ) ? $alts [$key] : '';
							$media->thumb_file = $thumbnFile;
							$media->original_name = $name;
							$media->title = isset ( $model->title ) ? $model->title : 'Not set';
							$media->save ();
						}
					}
				}
			}
		}
	}
}
