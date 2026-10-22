<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\DrivingLicense;

/**
 * app\modules\admin\models\search\DrivingLicenseSearch represents the model behind the search form about `app\modules\admin\models\DrivingLicense`.
 */
 class DrivingLicenseSearch extends DrivingLicense
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'driver_id', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['driving_license_front', 'driving_license_back', 'driving_license_number', 'issued_date', 'expire_date', 'updated_on', 'created_on'], 'safe'],
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
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = DrivingLicense::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_on' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'driver_id' => $this->driver_id,
            'issued_date' => $this->issued_date,
            'expire_date' => $this->expire_date,
            'status' => $this->status,
            'update_user_id' => $this->update_user_id,
            'create_user_id' => $this->create_user_id,
            'updated_on' => $this->updated_on,
            'created_on' => $this->created_on,
        ]);

        $query->andFilterWhere(['like', 'driving_license_front', $this->driving_license_front])
            ->andFilterWhere(['like', 'driving_license_back', $this->driving_license_back])
            ->andFilterWhere(['like', 'driving_license_number', $this->driving_license_number]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with managersearch query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function managersearch($params)
    {
        $query = DrivingLicense::find()
                     ->where(['city_id' => \Yii::$app->user->identity->city_id])
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_on' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'driver_id' => $this->driver_id,
            'issued_date' => $this->issued_date,
            'expire_date' => $this->expire_date,
            'status' => $this->status,
            'update_user_id' => $this->update_user_id,
            'create_user_id' => $this->create_user_id,
            'updated_on' => $this->updated_on,
            'created_on' => $this->created_on,
        ]);

        $query->andFilterWhere(['like', 'driving_license_front', $this->driving_license_front])
            ->andFilterWhere(['like', 'driving_license_back', $this->driving_license_back])
            ->andFilterWhere(['like', 'driving_license_number', $this->driving_license_number]);

        if(isset ($this->created_on)&&$this->created_on!=''){ 
           
           //you dont need the if function if yourse sure you have a not null date
            $date_explode=explode(" - ",$this->created_on);
         //   var_dump($date_explode);exit;
            $date1=trim($date_explode[0]);
           $date2=trim($date_explode[1]);
           $query->andFilterWhere(['between','created_on',$date1,$date2]);
          // var_dump($query->createCommand()->getRawSql());exit;
          }
       if(isset ($this->updated_on)&&$this->updated_on!=''){ 
      
           //you dont need the if function if yourse sure you have a not null date
            $date_explode=explode(" - ",$this->updated_on);
         //   var_dump($date_explode);exit;
            $date1=trim($date_explode[0]);
           $date2=trim($date_explode[1]);
           $query->andFilterWhere(['between','updated_on',$date1,$date2]);
          //  var_dump($query->createCommand()->getRawSql());exit;
          }

        return $dataProvider;
    }
}
