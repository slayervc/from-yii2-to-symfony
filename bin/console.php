#!/usr/bin/env php
<?php

use src\Common\Infrastructure\Symfony\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

if (!is_dir(dirname(__DIR__).'/vendor')) {
    throw new LogicException('Dependencies are missing. Try running "composer install".');
}

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    putenv('YII_APP_NAME=console');
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
