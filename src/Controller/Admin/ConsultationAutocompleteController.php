<?php

namespace App\Controller\Admin;

use App\Repository\ConsultationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ConsultationAutocompleteController extends AbstractController
{
    #[Route('/admin/consultations.json', name: 'admin_consultations_json', methods: ['GET'])]
    public function list(ConsultationRepository $consultationRepository): JsonResponse
    {
        $names = array_map(
            static fn ($consultation) => $consultation->getName(),
            $consultationRepository->findAll()
        );

        return new JsonResponse(array_values(array_unique($names)));
    }
}