<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Security;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: SecurityUserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 250, unique: true)]
    private ?string $email;

    #[ORM\Column(type: 'string', length: 250)]
    private string $password;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bizRoleId;

    #[ORM\Column(type: 'string', length: 250)]
    private ?string $privilege = null;

    private array $bizPermissions = [];

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $privilege = 'ROLE_' . strtoupper($this->privilege);
        return array_merge(
            [$privilege],
            $this->bizPermissions
        );
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBizRoleId(): ?int
    {
        return $this->bizRoleId;
    }
}