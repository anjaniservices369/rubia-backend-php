<?php
namespace app\components;

use Yii;
use app\models\User;
use yii\base\Component;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\Order;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\DriverAssigned;

class Dashboard extends Component
{

    //Get Total Order
    public function getOrders($type = '',$model)
    {
        $date = date('Y-m-d');
        $yesterday = date("Y-m-d", strtotime("yesterday"));
        // var_dump($type);
        if(!empty($type)){
        switch ($type) {

            case 'today':
                $count = $model::find()->andWhere(['like', 'created_on', $date])->count();
                return $count;
                break;

            case 'yesterday':
                $count = $model::find()->where(['status' => $type])->andWhere(['like', 'created_on', $yesterday])->count();
                return $count;
                break;

           case $model::STATUS_ORDERED:
                    $count = $model::find()->where(['status' => $type])->andWhere(['like', 'created_on', $date])->count();
                    // echo $count->createCommand()->getRawSql();exit;
                    return $count;
                    break;
            case $model::STATUS_ASSIGNED_DELIVERY_BOY:
                $count = $model::find()->where(['status' => $type])->andWhere(['like', 'created_on', $date])->count();
                // echo $count->createCommand()->getRawSql();exit;
                return $count;
                break;

            case $model::STATUS_ONTHE_WAY:
                $count = $model::find()->where(['status' => $type])->andWhere(['like', 'created_on', $date])->count();
                return $count;
                break;

            case $model::STATUS_DELIVERED:
 
                $count = $model::find()->where(['status' => $type])->andWhere(['like', 'created_on', $date])->count();
                return $count;
                break;

           

            default: /* Optional */
                $all_orders_count = $model::find()->count();
                return $all_orders_count;
        }
    }else{
        $all_orders_count = $model::find()->count();
        return $all_orders_count; 
    }


    }

    public function getUsers($user_role = '',$model)
    {
        if(!empty($user_role)){
            $count = $model::find()->where(['user_role'=>$user_role])->count();
        }else{
            $count = $model::find()->count();
        }

       
        return $count;

    }

    public function getListOrders($model,$limit,$status)
    {
        if(!empty($limit) && !empty($status)){
            $list = $model::find()->where(['status'=>$status])->limit($limit);
        }else{
            $list = $model::find()->limit(10);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $list,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);
       
        return $dataProvider;

    }

    public function getTemples($status = '',$model)
    {
        if (User::isAdmin()) {
        if(!empty($status)){
            $count = $model::find()->where(['is_open'=>$status])->count();
        }else{
            $count = $model::find()->count();
        }
        }else  if (User::isManager()) {
            if(!empty($status)){
                $count = $model::find()->where(['is_open'=>$status])->andWhere(['city_id'=>\Yii::$app->user->identity->city_id])->count();
            }else{
                $count = $model::find()->where(['city_id'=>\Yii::$app->user->identity->city_id])->count();
            }
        }

       
        return $count;

    }
  
}
