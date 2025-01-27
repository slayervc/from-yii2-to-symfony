<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly SecurityUserRepository $userRepository
    ) {
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->loadUserByIdentifier($identifier);
        if (null === $user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }
}