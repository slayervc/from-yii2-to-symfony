<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\EventListener;

use common\models\user\User;
use src\Common\Infrastructure\Yii2\ApplicationLoader;
use src\Common\Infrastructure\Yii2\Http\ResponseBridge;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use yii\web\NotFoundHttpException as YiiNotFoundHttpException;

#[AsEventListener(priority: -1000)]
class YiiApplicationRunner
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader,
        private readonly Security $security,
        private readonly string $symfonyAppEnv
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->applicationLoader->isControlTransferred()) {
            return;
        }

        try {
            $config = $this->security->getFirewallConfig($event->getRequest());
            $application = $this->applicationLoader->getApp();
            if (null !== $config && $config->isSecurityEnabled()) {
                $symfonyUser = $this->security->getUser();
                $usersEqual = $application->user?->getId() === $symfonyUser?->getId();
                if (null === $symfonyUser || !$usersEqual) {
                    $application->user->logout();
                }

                if (null !== $symfonyUser && !$usersEqual) {
                    $yiiUser = User::findByUsernameOrEmail($symfonyUser->getUserIdentifier());
                    $application->user->login($yiiUser);
                }
            }

            $response = $application->run();
            $response->send();
        } catch (YiiNotFoundHttpException) {
            throw $this->applicationLoader->getSymfonyException();
        } catch (\Throwable $e) {
            if ('prod' !== $this->symfonyAppEnv) {
                throw $e;
            }

            $errorHandler = $this->applicationLoader->getApp()->getErrorHandler();
            $errorHandler->silentExitOnException = true;
            $errorHandler->handleException($e);
        }

        $symfonyResponse = ResponseBridge::yiiToSymfony($this->applicationLoader->getApp()->getResponse());
        $event->setResponse($symfonyResponse);
    }
}