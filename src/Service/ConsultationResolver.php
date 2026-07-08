<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ConsultationResolver
{
    public function __construct(
        private readonly ConsultationRepository $consultationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Retrouve un type de consultation existant par son nom (insensible à la casse),
     * ou en crée un nouveau si aucun ne correspond. Le nouveau type est mis en attente
     * de persistance (persist), le flush reste à la charge de l'appelant.
     */
    public function resolveOrCreate(string $rawName): Consultation
    {
        $name = trim($rawName);

        if ($name === '') {
            throw new \InvalidArgumentException('Le type de consultation est obligatoire.');
        }

        foreach ($this->consultationRepository->findAll() as $consultation) {
            if (mb_strtolower($consultation->getName()) === mb_strtolower($name)) {
                return $consultation;
            }
        }

        $consultation = new Consultation();
        $consultation->setName($name);
        $consultation->setDurationMinutes(30);
        $consultation->setIsActive(true);

        $this->entityManager->persist($consultation);

        return $consultation;
    }
}