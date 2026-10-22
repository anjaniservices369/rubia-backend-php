<?php

namespace app\modules\cartaxirider;

/**
 * Car taxi: the customer (rider) API.
 * The behaviour is shared with the other taxi services (modules/taxicore); this module only says which service it is, so
 * every ride, vehicle and offer it touches is limited to Car taxi.
 */
class Module extends \app\modules\taxicore\rider\Module
{
    public $serviceModule = 2;
}
