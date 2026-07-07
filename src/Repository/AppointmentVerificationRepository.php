<?php

namespace App\Repository;

use App\Entity\AppointmentVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppointmentVerification>
 */
class AppointmentVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppointmentVerification::class);
    }

    /**
     * Retourne les vérifications en cours (non expirées) qui chevauchent la période donnée,
     * pour bloquer temporairement ces créneaux le temps que le client saisisse son code.
     *
     * @return AppointmentVerification[]
     */
    public function findActiveBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.expiresAt > :now')
            ->andWhere('v.startAt < :end')
            ->andWhere('v.endAt > :start')
            ->setParameter('now', new \DateTime())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}