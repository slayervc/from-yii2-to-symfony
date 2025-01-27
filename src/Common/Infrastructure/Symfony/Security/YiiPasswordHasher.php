<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use yii\base\Security;

class YiiPasswordHasher implements PasswordHasherInterface
{
    private readonly Security $yiiSecurity;

    public function __construct()
    {
        $this->yiiSecurity = new Security();
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->yiiSecurity->generatePasswordHash($plainPassword);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        return $this->yiiSecurity->validatePassword($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}