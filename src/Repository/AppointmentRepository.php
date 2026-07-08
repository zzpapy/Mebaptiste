<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findActiveBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.startAt BETWEEN :start AND :end')
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les rendez-vous actifs (non annulés) qui chevauchent la plage donnée,
     * en excluant éventuellement un rendez-vous précis (utile lors d'une modification
     * pour ne pas se bloquer soi-même).
     *
     * @return Appointment[]
     */
    public function findOverlapping(\DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt > :start')
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCancelToken(string $token): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.cancelToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les X prochains rendez-vous actifs (non annulés), à partir de maintenant.
     *
     * @return Appointment[]
     */
    public function findUpcoming(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.startAt >= :now')
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}