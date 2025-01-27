<?php

use src\Common\Infrastructure\Symfony\Kernel;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

require __DIR__ . '/../../vendor/autoload_runtime.php';

return function (array $context) {
    putenv('YII_APP_NAME=api');

    return new Kernel($context['APP_ENV'], (bool)$context['APP_DEBUG']);
};
