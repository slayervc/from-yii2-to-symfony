<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AppKindRequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private readonly string $currentAppKind,
        private readonly string $requiredAppKind,
        private readonly array $paths = []
    ) {
    }

    public function matches(Request $request): bool
    {
        if ($this->currentAppKind !== $this->requiredAppKind) {
            return false;
        }

        if (empty($this->paths)) {
            return true;
        }

        foreach ($this->paths as $path) {
            if (preg_match('{'.$path.'}', rawurldecode($request->getPathInfo()))) {
                return true;
            }
        }

        return false;
    }
}