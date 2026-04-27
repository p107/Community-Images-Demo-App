<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\PhotoFilterDTO;
use App\Entity\Photo;
use App\Shared\Photo\PhotoRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository implements PhotoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithFilters(PhotoFilterDTO $filter): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.id', 'ASC');

        if ($filter->location !== null) {
            $qb->andWhere('p.location LIKE :location')
               ->setParameter('location', '%' . $filter->location . '%');
        }

        if ($filter->camera !== null) {
            $qb->andWhere('p.camera LIKE :camera')
               ->setParameter('camera', '%' . $filter->camera . '%');
        }

        if ($filter->description !== null) {
            $qb->andWhere('p.description LIKE :description')
               ->setParameter('description', '%' . $filter->description . '%');
        }

        if ($filter->takenAtFrom !== null) {
            $qb->andWhere('p.takenAt >= :takenAtFrom')
               ->setParameter('takenAtFrom', $filter->takenAtFrom);
        }

        if ($filter->takenAtTo !== null) {
            $qb->andWhere('p.takenAt <= :takenAtTo')
               ->setParameter('takenAtTo', $filter->takenAtTo->modify('+1 day'));
        }

        if ($filter->username !== null) {
            $qb->andWhere('u.username LIKE :username')
               ->setParameter('username', '%' . $filter->username . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
