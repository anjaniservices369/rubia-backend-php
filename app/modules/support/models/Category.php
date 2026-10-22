<?php

namespace app\modules\support\models;

use Yii;
use app\models\User;

/**
 * This is the model class for table "support_category".
 *
 * @property int $id
 * @property string $parent_id
 * @property string $title
 * @property int $state_id
 * @property int $type_id
 * @property string $created_on
 * @property string $updated_on
 * @property int $create_user_id
 *
 * @property User $createUser
 * @property User $createUser0
 * @property SupportSolution[] $supportSolutions
 */
class Category extends \yii\db\ActiveRecord {
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'support_category';
	}
	
	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [ 
				[ 
						[ 
								'title',
								'create_user_id' 
						],
						'required' 
				],
				[ 
						[ 
								'state_id',
								'type_id',
								'create_user_id' 
						],
						'integer' 
				],
				[ 
						[ 
								'created_on',
								'updated_on' 
						],
						'safe' 
				],
				[ 
						[ 
								'parent_id',
								'title' 
						],
						'string',
						'max' => 512 
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
	
	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [ 
				'id' => 'ID',
				'parent_id' => 'Parent ID',
				'title' => 'Title',
				'state_id' => 'State ID',
				'type_id' => 'Type ID',
				'created_on' => 'Created On',
				'updated_on' => 'Updated On',
				'create_user_id' => 'Create User ID' 
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
	
	/**
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupportSolutions() {
		return $this->hasMany ( Solution::className (), [ 
				'category_id' => 'id' 
		] );
	}
}
