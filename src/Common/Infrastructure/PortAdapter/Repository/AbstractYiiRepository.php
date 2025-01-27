<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\PortAdapter\Repository;

use src\Common\Infrastructure\Yii2\ApplicationLoader;

abstract class AbstractYiiRepository
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader
    ) {
        $this->applicationLoader->load();
    }
}