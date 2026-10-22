<?php

namespace app\modules\admin\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\User;

/**
 * UserSearch represents the model behind the search form of `app\models\User`.
 */
class UserSearch extends User
{
	public $full_name;
  
  public $profile_verified;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id', 'status', 'city_id'], 'integer'],
			[['username', 'email', 'full_name', 'contact_no', 'first_name','profile_verified'], 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		// bypass scenarios() implementation in the parent class
		return Model::scenarios();
	}

	/**
	 * The condition "belongs to this city" (see search()).
	 */
	public static function inCity($cityId)
	{
		return ['or', ['user.city_id' => (int) $cityId], ['user.id' => (new \yii\db\Query())->select('user_id')->from('driver_details')->where(['city_id' => (int) $cityId])]];
	}

	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ActiveDataProvider
	 */

	public function search($params, $role = '', $user_id = null, $type = '',$city_id="")
	{
		$query = !empty($role) ? User::find()->Where(['user_role' => $role]) : User::find();
		if (!empty($role) && !empty($type)) {
			$query->andWhere(['account_type' => $type]);
		}
		// a franchise sees only the people of its city: the city found from the person's address (user.city_id), or the city the
		// partner chose while joining (driver_details.city_id)
		if (!empty($city_id)) {
			$query->andWhere(self::inCity($city_id));
		}


		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'id',
					'status',
					'username',
					'email',
					'created_at',
					'contact_no',
					'full_name' => [
						'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
						'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
					],
				],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			'id'         => $this->id,
			'status'     => $this->status,
			'created_at' => $this->created_at,
		]);

		if (!empty($this->city_id)) {
			$query->andWhere(self::inCity((int) $this->city_id));
		}
		$query->andFilterWhere(['like', 'username', $this->username])
			->andFilterWhere(['like', 'email', $this->email])
			->andFilterWhere(['like', 'first_name', $this->first_name])
			->andFilterWhere(['like', 'contact_no', $this->contact_no])
			->andFilterWhere(['like', 'concat(first_name, " " , last_name) ', $this->full_name]);
      
      // Add a condition to filter by 'profile_verified'
		 if ($this->profile_verified !== null) {
            $query->andFilterWhere(['profile_verified' => $this->profile_verified]);
        }


		return $dataProvider;
	}

	//// manager user search
	public function searchmanager($params, $role = '', $user_id = null, $type = '',$city_id="",$vehicle_id = "")
	{
		if (!empty($role)) {
			if (!empty($type)) {
				$query = User::find()->Where(['user_role' => $role])->andWhere(['account_type' => $type]);
			}
			elseif(!empty($city_id))
			{
				// $query = User::find()->Where(['user_role' => $role])->andWhere(['city_id' => $city_id]);
				$query = User::find()
				->select(['user.*'])
				->leftJoin('driver_details', 'driver_details.user_id = user.id')
				->Where(['user.user_role' => $role])
				->andWhere(['driver_details.city_id' => $city_id])
				->andWhere(['driver_details.vehical_id' => $vehicle_id]);
			}
			else
			{
				$query = User::find()->Where(['user_role' => $role]);
			}
			
			
			
		} else {
			$query = User::find();
		}


		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'id',
					'status',
					'username',
					'email',
					'created_at',
					'contact_no',
					'full_name' => [
						'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
						'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
					],
				],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			'id'         => $this->id,
			'status'     => $this->status,
			'created_at' => $this->created_at,
		]);

		$query->andFilterWhere(['like', 'username', $this->username])
			->andFilterWhere(['like', 'email', $this->email])
			->andFilterWhere(['like', 'first_name', $this->first_name])
			->andFilterWhere(['like', 'contact_no', $this->contact_no])
			->andFilterWhere(['like', 'concat(first_name, " " , last_name) ', $this->full_name]);


		return $dataProvider;
	}

	public function SubscribedUserSearch($params, $role = '', $user_id = null, $type = '')
	{
		if (!empty($role)) {

			$subUser = SubscriptionOrders::find()->all();
			foreach ($subUser as $sub) {

				$query = User::find()->Where(['user_role' => $role])->andWhere(['id' => $sub->user_id]);
			}
		} else {
			$query = User::find();
		}


		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'id',
					'status',
					'username',
					'email',
					'created_at',
					'contact_no',
					'full_name' => [
						'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
						'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
					],
				],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			'id'         => $this->id,
			'status'     => $this->status,
			'created_at' => $this->created_at,
		]);

		$query->andFilterWhere(['like', 'username', $this->username])
			->andFilterWhere(['like', 'email', $this->email])
			->andFilterWhere(['like', 'first_name', $this->first_name])
			->andFilterWhere(['like', 'contact_no', $this->contact_no])
			->andFilterWhere(['like', 'concat(first_name, " " , last_name) ', $this->full_name]);


		return $dataProvider;
	}
	public function usersearch($params, $user_id = null)
	{
		if ($user_id != null) {
			$query = User::find()->where(['referal_id' => $user_id]);
		} else {
			$query = User::find()->where(['!=', 'id', \Yii::$app->user->id]);
		}
		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'id',
					'status',
					'username',
					'email',
					'created_at',
					'full_name' => [
						'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
						'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
					],
				],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		// grid filtering conditions
		$query->andFilterWhere([
			'id'         => $this->id,
			'status'     => $this->status,
			'created_at' => $this->created_at,
		]);

		$query->andFilterWhere(['like', 'username', $this->username])
			->andFilterWhere(['like', 'first_name', $this->first_name])
			->andFilterWhere(['like', 'email', $this->email])
			->andFilterWhere(['like', 'concat(first_name, " " , last_name) ', $this->full_name]);
		//$cmd =  $query->createCommand()->getRawSql();
		//var_dump($cmd); exit;

		return $dataProvider;
	}
}
