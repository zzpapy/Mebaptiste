<?php

namespace App\Repository;

use App\Entity\Blocage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blocage>
 */
class BlocageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blocage::class);
    }

    /**
     * Retourne les blocages dont la plage [startDate, endDate] chevauche la période donnée.
     *
     * @return Blocage[]
     */
    public function findOverlapping(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.startDate <= :end')
            ->andWhere('b.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}