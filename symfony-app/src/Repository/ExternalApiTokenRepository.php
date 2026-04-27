<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExternalApiToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExternalApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalApiToken::class);
    }

    public function findByUserAndService(User $user, string $serviceName): ?ExternalApiToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.serviceName = :serviceName')
            ->setParameter('user', $user)
            ->setParameter('serviceName', $serviceName)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

