<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\DriverDetails;

/**
 * app\modules\admin\models\search\DriverDetailsSearch represents the model behind the search form about `app\modules\admin\models\DriverDetails`.
 */
 class DriverDetailsSearch extends DriverDetails
{

    public $online_status;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'city_id', 'vehical_id', 'is_verified', 'status',  'create_user_id', 'update_user_id'], 'integer'],
            [['commission_percent'], 'number'],
            [['address', 'lat', 'lng', 'location', 'license_no', 'proof_of_license', 'proof_of_license_back', 'rc_number', 'vehical_number', 'rc_proof', 'rc_proof_back', 'adhaar_number', 'adhaar_front', 'adhaar_back', 'pan_number', 'pan_front', 'pan_back', 'created_on', 'updated_on','online_status'], 'safe'],
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
    public function search($params,$vehicle_id="",$city_id ="")
    {
        // var_dump($params);exit;

       if($city_id != "")
       {
        $query = DriverDetails::find()->where(['vehical_id'=>$vehicle_id])->andWhere(['driver_details.city_id'=>$city_id])->joinWith(['user as u']);
       }
       else
       {
        $query = DriverDetails::find()->where(['vehical_id'=>$vehicle_id])->joinWith(['user as u']);
       }
        

        // $model = DriverDetails::find()->one();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
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
            'driver_details.id' => $this->id,
            'driver_details.user_id' => $this->user_id,
            'driver_details.commission_percent' => $this->commission_percent,
            'driver_details.city_id' => $this->city_id,
            'driver_details.vehical_id' => $this->vehical_id,
            'driver_details.is_verified' => $this->is_verified,
            'driver_details.status' => $this->vehical,
            // 'u.online_status' => $this->user->online_status??"",
            // 'form_steps' => $this->form_steps,
            // 'document_steps' => $this->document_steps,
            'driver_details.created_on' => $this->created_on,
            'driver_details.updated_on' => $this->updated_on,
            'driver_details.create_user_id' => $this->create_user_id,
            'driver_details.update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'driver_details.address', $this->address])
            ->andFilterWhere(['like', 'driver_details.lat', $this->lat])
            ->andFilterWhere(['like', 'driver_details.lng', $this->lng])
            ->andFilterWhere(['like', 'driver_details.location', $this->location])
            ->andFilterWhere(['like', 'driver_details.license_no', $this->license_no])
            ->andFilterWhere(['like', 'driver_details.proof_of_license', $this->proof_of_license])
            ->andFilterWhere(['like', 'driver_details.proof_of_license_back', $this->proof_of_license_back])
            ->andFilterWhere(['like', 'driver_details.rc_number', $this->rc_number])
            ->andFilterWhere(['like', 'driver_details.vehical_number', $this->vehical_number])
            ->andFilterWhere(['like', 'u.online_status', $this->online_status])
            ->andFilterWhere(['like', 'driver_details.rc_proof', $this->rc_proof])
            ->andFilterWhere(['like', 'driver_details.rc_proof_back', $this->rc_proof_back])
            ->andFilterWhere(['like', 'driver_details.adhaar_number', $this->adhaar_number])
            ->andFilterWhere(['like', 'driver_details.adhaar_front', $this->adhaar_front])
            ->andFilterWhere(['like', 'driver_details.adhaar_back', $this->adhaar_back])
            ->andFilterWhere(['like', 'driver_details.pan_number', $this->pan_number])
            ->andFilterWhere(['like', 'driver_details.pan_front', $this->pan_front])
            ->andFilterWhere(['like', 'driver_details.pan_back', $this->pan_back]);

// print_r($query->createCommand()->getRawSql());exit;
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
        $query = DriverDetails::find()
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
            'user_id' => $this->user_id,
            'commission_percent' => $this->commission_percent,
            'city_id' => $this->city_id,
            'vehical_id' => $this->vehical_id,
            'is_verified' => $this->is_verified,
            'status' => $this->status,
            // 'form_steps' => $this->form_steps,
            // 'document_steps' => $this->document_steps,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'address', $this->address])
            ->andFilterWhere(['like', 'lat', $this->lat])
            ->andFilterWhere(['like', 'lng', $this->lng])
            ->andFilterWhere(['like', 'location', $this->location])
            ->andFilterWhere(['like', 'license_no', $this->license_no])
            ->andFilterWhere(['like', 'proof_of_license', $this->proof_of_license])
            ->andFilterWhere(['like', 'proof_of_license_back', $this->proof_of_license_back])
            ->andFilterWhere(['like', 'rc_number', $this->rc_number])
            ->andFilterWhere(['like', 'vehical_number', $this->vehical_number])
            ->andFilterWhere(['like', 'rc_proof', $this->rc_proof])
            ->andFilterWhere(['like', 'rc_proof_back', $this->rc_proof_back])
            ->andFilterWhere(['like', 'adhaar_number', $this->adhaar_number])
            ->andFilterWhere(['like', 'adhaar_front', $this->adhaar_front])
            ->andFilterWhere(['like', 'adhaar_back', $this->adhaar_back])
            ->andFilterWhere(['like', 'pan_number', $this->pan_number])
            ->andFilterWhere(['like', 'pan_front', $this->pan_front])
            ->andFilterWhere(['like', 'pan_back', $this->pan_back]);

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
