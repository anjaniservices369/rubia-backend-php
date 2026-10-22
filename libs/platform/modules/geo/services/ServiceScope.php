<?php

namespace app\modules\geo\services;

use Yii;

/**
 * Binds one API request to ONE service (bike, auto or car).
 *
 * The three taxi services run on their own addresses (/api/bike, /api/auto, /api/car and the matching -user ones).
 * Each address is a module instance with `serviceModule` set; its beforeAction calls enter(), after which:
 *   - every ride read through RideRequest (lists, lookups by id, counts) is limited to that service's vehicle types
 *     (RideRequestQuery), so a bike partner can never see, accept or change an auto ride and vice versa;
 *   - the vehicle a partner registers or a rider books must belong to the service;
 *   - the vehicle list is the service's own.
 * The original shared /api/taxi/* and /api/taxi-user/* addresses have no service bound and behave as before.
 */
class ServiceScope
{
    private static $module = null;
    private static $commercial = null;

    // [doc:start] enter - Enter
    /**
     * @return bool false when the request was refused (the response is already set)
     */
    public static function enter($module, $actionId)
    {
        self::$module = $module ? (int) $module : null;
        self::$commercial = self::$module;
        if (self::$module === null) {
            return true;
        }
        $request = Yii::$app->request;
        $body = $request->getBodyParams();

        // the vehicle list of this address is the service's own, whatever the app asks for
        if (array_key_exists('vehicle_type', $body) || in_array($actionId, ['get-vehicles', 'vehicals'], true)) {
            $body['vehicle_type'] = self::$module;
            $request->setBodyParams($body);
        }

        $own = self::vehicleIds();
        if (isset($body['vehical_id']) && $body['vehical_id'] !== '' && !in_array((int) $body['vehical_id'], $own, true)) {
            return self::refuse('This vehicle is not part of the ' . ServiceModules::title(self::$module) . ' service.');
        }
        if (isset($body['vehical_type']) && $body['vehical_type'] !== '' && (int) $body['vehical_type'] !== self::$module
            && in_array($actionId, ['update-vehicals'], true)) {
            return self::refuse('This vehicle type is not part of the ' . ServiceModules::title(self::$module) . ' service.');
        }

        return true;
    }
    // [doc:end] enter

    // [doc:start] commercial - The service whose coupons and wallets this request deals in (null = not decided yet). The customer app's API is.
    /**
     * The service whose coupons and wallets this request deals in (null = not decided yet). The customer app's API is
     * the driver service; a service-bound taxi address is that service.
     */
    public static function commercial()
    {
        return self::$commercial;
    }
    // [doc:end] commercial

    // [doc:start] setCommercial - Set Commercial
    public static function setCommercial($module)
    {
        self::$commercial = $module ? (int) $module : null;
    }
    // [doc:end] setCommercial

    // [doc:start] module - The bound service, or null when the request is not bound to one.
    /** The bound service, or null when the request is not bound to one. */
    public static function module()
    {
        return self::$module;
    }
    // [doc:end] module

    // [doc:start] vehicleIds - Vehicle type ids (`vehicals.id`) of the bound service, or null when no service is bound.
    /**
     * Vehicle type ids (`vehicals.id`) of the bound service, or null when no service is bound.
     *
     * @return int[]|null
     */
    public static function vehicleIds()
    {
        return self::$module === null ? null : ServiceModules::vehicleIds(self::$module);
    }
    // [doc:end] vehicleIds

    private static function refuse($message)
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = ['status' => 'NOK', 'error' => $message];

        return false;
    }
}
