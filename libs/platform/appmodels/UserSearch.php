<?php

namespace app\models;

use app\models\User;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form about `app\models\User`.
 */
class UserSearch extends User {
	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [ 
				[ 
						[ 
								'id',
								'status',
								'created_on',
								'updated_on' 
						],
						'integer' 
				],
				[ 
						[ 
								'username',
								'email' ,
								'contact_no'
						],
						'safe' 
				] 
		];
	}
	
	/**
	 * @inheritdoc
	 */
	public function scenarios() {
		// bypass scenarios() implementation in the parent class
		return Model::scenarios ();
	}
	
	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params        	
	 *
	 * @return ActiveDataProvider
	 */
	public function search($params,$role='',$flag=false) {

		
	    if($flag){
            $query = User::find ()->where(['create_user_id'=>\Yii::$app->user->id]);
        }else if(!empty($role)){
            $query = User::find ()->where(['role_id'=> $role]);

        }else{
			$query = User::find ();

		}

		// add conditions that should always apply here
		
		$dataProvider = new ActiveDataProvider ( [ 
				'query' => $query,
				'sort' => [
					'defaultOrder' => [
						'id' => SORT_DESC,
					]
				], 
		] );
		
		$this->load ( $params );
		
		if (! $this->validate ()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}
		
		// grid filtering conditions
		$query->andFilterWhere ( [ 
				'id' => $this->id,
				'status' => $this->status,
				'created_on' => $this->created_on,
				'updated_on' => $this->updated_on 
		] );
		
		$query->andFilterWhere ( [ 'like','username',$this->username ] )
		->andFilterWhere ( [ 'like','email',$this->email ] ) 
		->andFilterWhere(['like','contact_no',$this->contact_no]);
		
		return $dataProvider;
	}
}
