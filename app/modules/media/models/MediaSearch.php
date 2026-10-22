<?php

namespace app\modules\media\models;

use app\modules\media\models\Media;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MediaSearch represents the model behind the search form of `app\models\Media`.
 */
class MediaSearch extends Media {
	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [ 
				[ 
						[ 
								'id',
								'model_id',
								'state_id',
								'type_id',
								'create_user_id' 
						],
						'integer' 
				],
				[ 
						[ 
								'model_type',
								'size',
								'file_name',
								'thumb_file',
								'original_name',
								'extension',
								'created_on',
								'updated_on' 
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
	public function search($params) {
		$query = Media::find ();
		
		// add conditions that should always apply here
		
		$dataProvider = new ActiveDataProvider ( [ 
				'query' => $query,
				'sort' => [ 
						'defaultOrder' => [ 
								'id' => SORT_ASC 
						] 
				] 
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
				'model_id' => $this->model_id,
				'state_id' => $this->state_id,
				'created_on' => $this->created_on,
				'updated_on' => $this->updated_on,
				'create_user_id' => $this->create_user_id 
		] );
		
		$query->andFilterWhere ( [ 
				'like',
				'model_type',
				$this->model_type 
		] )->andFilterWhere ( [ 
				'like',
				'size',
				$this->size 
		] )->andFilterWhere ( [ 
				'like',
				'file_name',
				$this->file_name 
		] )->andFilterWhere ( [ 
				'like',
				'thumb_file',
				$this->thumb_file 
		] )->andFilterWhere ( [ 
				'like',
				'original_name',
				$this->original_name 
		] )->andFilterWhere ( [ 
				'like',
				'extension',
				$this->extension 
		] );
		
		return $dataProvider;
	}
}
