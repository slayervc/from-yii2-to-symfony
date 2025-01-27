<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\EventListener;

use src\Common\Infrastructure\Yii2\ApplicationLoader;
use src\Common\Infrastructure\Yii2\Http\CookieBridge;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener()]
class YiiCookieEventListener
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->applicationLoader->isLoaded()) {
            return;
        }

        $response = $event->getResponse();
        $cookies = CookieBridge::getYiiApplicationCookiesAsSymfonyCookies($this->applicationLoader->getApp());
        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
    }
}