<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2;

use Psr\Log\LoggerInterface;
use yii\helpers\ArrayHelper;

class ApplicationLoader
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $yiiAppName,
        private readonly LoggerInterface $logger,
        private ?Application $app = null,
        private bool $controlTransferred = false,
        private ?\Throwable $symfonyException = null,
    ) {
    }

    public function load(): void
    {
        if (null !== $this->app) {
            return;
        }

        require $this->projectRoot . '/vendor/yiisoft/yii2/Yii.php';
        require $this->projectRoot . '/common/helpers/shortcuts.php';
        require $this->projectRoot . '/common/config/bootstrap.php';

        $localBootstrap = $this->projectRoot . '/' . $this->yiiAppName . '/config/bootstrap.php';
        if (file_exists($localBootstrap)) {
            require $localBootstrap;
        }

        $localConfigFile = $this->projectRoot . '/' . $this->yiiAppName . '/config/main-local.php';
        $localConfig = [];
        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
        }

        $config = ArrayHelper::merge(
            require $this->projectRoot . '/common/config/main.php',
            require $this->projectRoot . '/common/config/main-local.php',
            require $this->projectRoot . '/' . $this->yiiAppName . '/config/main.php',
            $localConfig
        );

        $this->app = new Application($config);
        $this->app->initSymfonyComponents($this->logger);
    }

    public function getApp(): ?Application
    {
        if (null === $this->app) {
            $this->load();
        }

        return $this->app;
    }

    public function isLoaded(): bool
    {
        return null !== $this->app;
    }

    public function transferControl(\Throwable $symfonyException): void
    {
        $this->controlTransferred = true;
        $this->symfonyException = $symfonyException;
    }

    public function isControlTransferred(): bool
    {
        return $this->controlTransferred;
    }

    public function getSymfonyException(): \Throwable
    {
        if (null === $this->symfonyException) {
            throw new \LogicException('Symfony exception was not set for Yii ApplicationLoader');
        }

        return $this->symfonyException;
    }
}