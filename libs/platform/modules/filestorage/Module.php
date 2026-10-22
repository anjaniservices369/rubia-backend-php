<?php

namespace app\modules\filestorage;

use app\modules\api\ApiModule;

/**
 * File storage.
 *
 * Upload and serve images/documents (MediaStore). Routes /api/media/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\filestorage\controllers';
}