<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use yii\web\Response as YiiResponse;

abstract class ResponseBridge
{
    public static function yiiToSymfony(YiiResponse $yiiResponse): SymfonyResponse
    {
        $symfonyResponse = new SymfonyResponse();
        $symfonyResponse->setStatusCode($yiiResponse->getStatusCode());
        $symfonyResponse->headers->add($yiiResponse->getHeaders()->toArray());
        $symfonyResponse->setContent($yiiResponse->content);

        return $symfonyResponse;
    }
}