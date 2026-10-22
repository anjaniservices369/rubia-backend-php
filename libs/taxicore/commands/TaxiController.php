<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\admin\models\base\RideRequest;
use app\modules\dispatch\services\OrderDispatch;

/**
 * Taxi ride maintenance.
 *
 *   php yii taxi/expire-stale [--hours=24] [--apply]
 *
 * Closes rides that are still "waiting for a driver" after the given number of hours, with the same outcome as
 * the automatic timeout (status cancelled, reason "No driver accepted the ride", pending offers withdrawn).
 * Without --apply it only lists them.
 */
class TaxiController extends Controller
{
    /** @var int */
    public $hours = 24;
    /** @var bool */
    public $apply = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['hours', 'apply']);
    }
    // [doc:end] options

    // [doc:start] actionExpireStale - Expire Stale
    public function actionExpireStale()
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . (int) $this->hours . ' hours'));
        $rides = RideRequest::find()
            ->where(['status' => RideRequest::STATUS_NEW_REQUEST])
            ->andWhere(['<', 'created_on', $cutoff])
            ->all();

        $this->stdout(count($rides) . " ride(s) waiting for a driver since before $cutoff\n");
        if (!$this->apply) {
            foreach (array_slice($rides, 0, 10) as $ride) {
                $this->stdout("  ride {$ride->id} created {$ride->created_on}\n");
            }
            $this->stdout("dry run - add --apply to close them\n");
            return ExitCode::OK;
        }

        // The timeout check compares against params['dispatch']['offerTimeout']; closing is always wanted here.
        Yii::$app->params['dispatch']['offerTimeout'] = 1;
        $dispatch = new OrderDispatch();
        $closed = 0;
        foreach ($rides as $ride) {
            if ($dispatch->expireIfUnanswered($ride)) {
                $closed++;
            }
        }
        $this->stdout("closed $closed ride(s)\n");

        return ExitCode::OK;
    }
    // [doc:end] actionExpireStale
}