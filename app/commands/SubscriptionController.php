<?php

namespace app\commands;

use app\modules\admin\models\DriverSubscription;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Partner subscriptions.
 *
 *   php yii subscription/expire            how many subscriptions ended but are still flagged active (a dry run)
 *   php yii subscription/expire --apply    move them to inactive (run it daily, for example from cron: 0 1 * * * php yii subscription/expire --apply)
 *
 * The lists and the app already treat an ended subscription as inactive (DriverSubscription::isCurrent); this keeps the stored flag honest.
 * It is also done once a day by the first driver call after midnight, so a missing job does no harm.
 */
class SubscriptionController extends Controller
{
    /** @var bool actually change the data (default is a dry run) */
    public $apply = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['apply']);
    }
    // [doc:end] options

    // [doc:start] actionExpire - Moves the subscriptions whose end date has been reached from active to inactive. A dry run unless --apply is given. Safe to run again.
    /**
     * Moves the subscriptions whose end date has been reached from active to inactive. A dry run unless --apply is given. Safe to run again.
     */
    public function actionExpire()
    {
        $pending = (int) DriverSubscription::find()->where(['status' => DriverSubscription::STATUS_ACTIVE])->andWhere(['<=', 'end_date', date('Y-m-d')])->count();
        if (!$this->apply) {
            $this->stdout($pending . " subscription(s) ended but are still flagged active. Add --apply to move them to inactive.\n");

            return ExitCode::OK;
        }
        $this->stdout(DriverSubscription::expireEnded() . " subscription(s) moved to inactive.\n");

        return ExitCode::OK;
    }
    // [doc:end] actionExpire
}
