<?php

namespace app\glue;

/**
 * Transitional, for the shared process only (index.php): /api/user/* answers the user app's actions and the Halting drivers
 * booking flow together, exactly as before the split. The customer service and the Halting drivers service each answer
 * their own part with their own controller; this class is not part of either release.
 */
class UserController extends \app\modules\haltingdrivercustomer\controllers\UserController
{
    use \app\modules\customerapp\controllers\UserAppActions;
}
