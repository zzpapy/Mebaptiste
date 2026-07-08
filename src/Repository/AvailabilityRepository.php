<?php

namespace App\Repository;

use App\Entity\Availability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Availability>
 */
class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    public function findActiveRecurring(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.isActive = true')
            ->setParameter('type', Availability::TYPE_RECURRING)
            ->getQuery()
            ->getResult();
    }

    public function findActivePunctualBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.isActive = true')
            ->andWhere('a.date BETWEEN :start AND :end')
            ->setParameter('type', Availability::TYPE_PUNCTUAL)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les disponibilités actives qui chevauchent la même plage horaire,
     * pour le même jour de semaine (récurrente) ou la même date (ponctuelle),
     * en excluant éventuellement une disponibilité précise (utile en modification).
     *
     * @return Availability[]
     */
    public function findOverlapping(Availability $candidate, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.isActive = true')
            ->andWhere('a.startTime < :endTime')
            ->andWhere('a.endTime > :startTime')
            ->setParameter('type', $candidate->getType())
            ->setParameter('startTime', $candidate->getStartTime())
            ->setParameter('endTime', $candidate->getEndTime());

        if ($candidate->isRecurring()) {
            $qb->andWhere('a.dayOfWeek = :dayOfWeek')
                ->setParameter('dayOfWeek', $candidate->getDayOfWeek());
        } else {
            $qb->andWhere('a.date = :date')
                ->setParameter('date', $candidate->getDate());
        }

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}