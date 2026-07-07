<?php

namespace App\Controller\Admin;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/agenda')]
#[IsGranted('ROLE_ADMIN')]
class CalendarController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    #[Route('', name: 'admin_agenda', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/calendar/index.html.twig');
    }

    #[Route('/events', name: 'admin_agenda_events', methods: ['GET'])]
    public function events(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        $startDate = $start ? new \DateTimeImmutable($start) : new \DateTimeImmutable('-1 month');
        $endDate = $end ? new \DateTimeImmutable($end) : new \DateTimeImmutable('+1 month');

        $appointments = $this->appointmentRepository->findActiveBetween($startDate, $endDate);

        $events = [];
        foreach ($appointments as $appointment) {
            $color = match ($appointment->getStatus()) {
                'confirmed' => '#1a3a5c',
                'pending' => '#c9a24b',
                default => '#999999',
            };

            $events[] = [
                'id' => $appointment->getId(),
                'title' => sprintf(
                    '%s %s - %s',
                    $appointment->getClientFirstName(),
                    $appointment->getClientLastName(),
                    $appointment->getConsultation()?->getName() ?? ''
                ),
                'start' => $appointment->getStartAt()->format('c'),
                'end' => $appointment->getEndAt()->format('c'),
                'color' => $color,
            ];
        }

        return new JsonResponse($events);
    }
}