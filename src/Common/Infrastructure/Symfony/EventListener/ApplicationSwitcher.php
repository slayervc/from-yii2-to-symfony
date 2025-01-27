<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\EventListener;

use Psr\Log\LoggerInterface;
use src\Common\Infrastructure\Yii2\ApplicationLoader;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

#[AsDecorator(decorates: 'router_listener')]
class ApplicationSwitcher extends RouterListener
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader,
        UrlMatcherInterface|RequestMatcherInterface $matcher,
        RequestStack $requestStack,
        ?RequestContext $context = null,
        ?LoggerInterface $logger = null,
        ?string $projectDir = null,
        bool $debug = true,
    ) {
        parent::__construct($matcher, $requestStack, $context, $logger, $projectDir, $debug);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        try {
            parent::onKernelRequest($event);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException $e) {
            $this->applicationLoader->transferControl($e);
        }
    }
}