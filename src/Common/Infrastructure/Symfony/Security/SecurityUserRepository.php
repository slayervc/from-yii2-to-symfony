<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Symfony\Security;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityUserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $qb = $this->createQueryBuilder('u');

        /** @var User $user */
        $user = $qb
            ->where('u.email = :email')
            ->setParameter('email', $identifier)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $user->getBizRoleId()) {
            $permissions = $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->select('p.permission')
                ->from('permissions p')
                ->where('p.role_id = :roleId')
                ->setParameter('roleId', $user->getBizRoleId())
                ->fetchFirstColumn();
        }

        if (!empty($permissions)) {
            $permissions = array_map(fn (string $s) => 'ROLE_PERMISSION_' . strtoupper($s), $permissions);
            $this->setPropertyValue($user, 'bizPermissions', $permissions);
        }

        return $user;
    }

    private function setPropertyValue(object $object, string $property, $value): void
    {
        $reflectionObject = new \ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}