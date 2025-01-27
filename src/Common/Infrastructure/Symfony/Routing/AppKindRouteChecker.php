<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;
use Symfony\Component\HttpFoundation\Request;

#[AsRoutingConditionService(alias: 'app_kind_route_checker')]
class AppKindRouteChecker
{
    public function __construct(
        private readonly string $currentAppKind
    ) {
    }

    public function check(Request $request, string $requiredAppKind): bool
    {
        return $this->currentAppKind === $requiredAppKind;
    }
}