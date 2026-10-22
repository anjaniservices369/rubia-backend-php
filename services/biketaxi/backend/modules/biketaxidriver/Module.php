<?php

namespace app\modules\biketaxidriver;

/**
 * Bike taxi: the partner (driver) app API.
 * The behaviour is shared with the other taxi services (modules/taxicore); this module only says which service it is, so
 * every ride, vehicle and offer it touches is limited to Bike taxi.
 */
class Module extends \app\modules\taxicore\partner\Module
{
    public $serviceModule = 4;
}
