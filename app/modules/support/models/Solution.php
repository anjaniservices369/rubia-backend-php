<?php

namespace app\modules\support\models;

use Yii;
use app\models\User;

/**
 * This is the model class for table "support_solution".
 *
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $description
 * @property int $state_id
 * @property int $type_id
 * @property string $created_on
 * @property string $updated_on
 * @property int $create_user_id
 *
 * @property User $createUser
 * @property User $createUser0
 * @property User $createUser1
 * @property SupportCategory $category
 */
class Solution extends \yii\db\ActiveRecord {
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'support_solution';
	}
	
	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [ 
				[ 
						[ 
								'category_id',
								'title',
								'description',
								'create_user_id' 
						],
						'required' 
				],
				[ 
						[ 
								'category_id',
								'state_id',
								'type_id',
								'create_user_id' 
						],
						'integer' 
				],
				[ 
						[ 
								'description' 
						],
						'string' 
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
								'category_id' 
						],
						'exist',
						'skipOnError' => true,
						'targetClass' => Category::className (),
						'targetAttribute' => [ 
								'category_id' => 'id' 
						] 
				] 
		];
	}
	
	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [ 
				'id' => Yii::t ( 'app', 'ID' ),
				'category_id' => Yii::t ( 'app', 'Category ID' ),
				'title' => Yii::t ( 'app', 'Title' ),
				'description' => Yii::t ( 'app', 'Description' ),
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
	
	/**
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreateUser1() {
		return $this->hasOne ( User::className (), [ 
				'id' => 'create_user_id' 
		] );
	}
	
	/**
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory() {
		return $this->hasOne ( Category::className (), [ 
				'id' => 'category_id' 
		] );
	}
}
