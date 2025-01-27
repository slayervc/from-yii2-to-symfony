<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2;

use Psr\Log\LoggerInterface;
use yii\base\ExitException;
use yii\web\Response;
use yii\web\Application as YiiWebApplication;

class Application extends YiiWebApplication
{
    private ?LoggerInterface $logger = null;

    public function run(): ?Response
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response;
        } catch (ExitException $e) {
            $this->end($e->statusCode, $response ?? null);

            return $response ?? null;
        }
    }

    public function initSymfonyComponents(
        LoggerInterface $logger
    ): void {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}