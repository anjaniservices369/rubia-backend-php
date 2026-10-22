<?php

// Loads config/secrets.php (git-ignored); returns [] if it does not exist.
return (static function () {
    $file = __DIR__ . '/secrets.php';
    return is_file($file) ? require $file : [];
})();