<?php

namespace app\modules\taxicore\partner\services;

use yii\base\Component;
use app\models\User;

/**
 * Availability and position of a taxi driver: where the driver is and whether they accept rides.
 * The dispatcher (OrderDispatch) and the admin live map read these values from the user record.
 */
class TaxiPresenceService extends Component
{
    // [doc:start] updateLocation - Update Location
    /**
     * @return array|null public position fields, or null when the user does not exist
     * @throws \InvalidArgumentException for coordinates outside the valid range
     */
    public function updateLocation($userId, $latitude, $longitude)
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)
            || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            throw new \InvalidArgumentException('Invalid coordinates');
        }

        $user = User::findOne($userId);
        if ($user === null) {
            return null;
        }
        $user->lat = (string) $latitude;
        $user->lng = (string) $longitude;
        $user->save(false);

        return [
            'id' => (int) $user->id,
            'lat' => $user->lat,
            'lng' => $user->lng,
            'online_status' => $user->online_status,
        ];
    }
    // [doc:end] updateLocation

    // [doc:start] setOnline - Set Online
    /**
     * @return int|null the stored online status, or null when the user is not a taxi driver
     */
    public function setOnline($userId, $status)
    {
        $driver = User::find()->where(['id' => $userId, 'user_role' => User::ROLE_TAXI])->one();
        if ($driver === null) {
            return null;
        }
        $driver->online_status = (int) $status;
        $driver->save(false);

        return (int) $driver->online_status;
    }
    // [doc:end] setOnline
}