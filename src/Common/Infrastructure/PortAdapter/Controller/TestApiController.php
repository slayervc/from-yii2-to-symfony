<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\PortAdapter\Controller;

use src\Common\Infrastructure\Yii2\ApplicationLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(condition: "service('app_kind_route_checker').check(request, 'api')")]
class TestApiController extends AbstractController
{
    public function __construct(
        private readonly ApplicationLoader $applicationLoader,
    ) {
    }

    #[Route('/test', name: 'api_test')]
    public function test(): Response
    {
        return new Response(
            '<html><body>Hi from API!</body></html>'
        );
    }
}