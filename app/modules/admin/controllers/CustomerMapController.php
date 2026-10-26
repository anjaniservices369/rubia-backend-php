<?php

namespace app\modules\admin\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Overview map of where every customer joined -- the same point each customer's own "Joined in" card already
 * shows (app/modules/admin/views/partials/joined_map.php), plotted together on one map instead of one at a
 * time. Admins and sub-admins only; a franchise already sees its own city's people on Customers-profile and
 * has no need for the whole map.
 */
class CustomerMapController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        },
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/customer-map/index - Every customer who has a saved point, on one map.
    /**
     * Every customer who has a saved point, on one map. Same "stored the other way round" correction as the
     * single-person map (India: latitude 6-37, longitude 68-98).
     */
    public function actionIndex()
    {
        $rows = User::find()
            ->select(['id', 'first_name', 'last_name', 'lat', 'lng', 'city_name'])
            ->where(['user_role' => User::ROLE_USER])
            ->andWhere(['<>', 'lat', ''])
            ->andWhere(['<>', 'lng', ''])
            ->andWhere(['is not', 'lat', null])
            ->andWhere(['is not', 'lng', null])
            ->asArray()
            ->all();

        $points = [];
        foreach ($rows as $row) {
            $lat = (float) $row['lat'];
            $lng = (float) $row['lng'];
            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }
            // some older records hold latitude and longitude the other way round (India: lat 6-37, lng 68-98)
            if ($lat >= 60 && $lat <= 100 && $lng >= 5 && $lng <= 40) {
                [$lat, $lng] = [$lng, $lat];
            }
            $name = trim($row['first_name'] . ' ' . $row['last_name']) ?: ('Customer #' . $row['id']);
            $points[] = [
                'id' => (int) $row['id'],
                'lat' => $lat,
                'lng' => $lng,
                'name' => $name,
                'city' => (string) $row['city_name'],
            ];
        }

        return $this->render('index', [
            'points' => $points,
            'totalCustomers' => User::find()->where(['user_role' => User::ROLE_USER])->count(),
        ]);
    }
    // [doc:end] actionIndex
}
