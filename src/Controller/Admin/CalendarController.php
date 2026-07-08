<?php

namespace App\Controller\Admin;

use App\Repository\AppointmentRepository;
use App\Repository\BlocageRepository;
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
        private readonly BlocageRepository $blocageRepository,
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
        $blocages = $this->blocageRepository->findOverlapping($startDate, $endDate);

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

        foreach ($blocages as $blocage) {
            $title = 'Bloqué';
            if ($blocage->getReason()) {
                $title .= ' - '.$blocage->getReason();
            }

            if ($blocage->isFullDay()) {
                // FullCalendar traite 'end' comme exclusif pour les événements allDay,
                // donc on ajoute un jour pour couvrir correctement la dernière journée.
                $endExclusive = \DateTimeImmutable::createFromInterface($blocage->getEndDate())
                    ->modify('+1 day');

                $events[] = [
                    'title' => $title,
                    'start' => $blocage->getStartDate()->format('Y-m-d'),
                    'end' => $endExclusive->format('Y-m-d'),
                    'allDay' => true,
                    'color' => '#b03030',
                    'display' => 'block',
                ];

                continue;
            }

            $currentDay = \DateTimeImmutable::createFromInterface($blocage->getStartDate());
            $lastDay = \DateTimeImmutable::createFromInterface($blocage->getEndDate());

            while ($currentDay <= $lastDay) {
                $startTime = $blocage->getStartTime();
                $endTime = $blocage->getEndTime();

                $events[] = [
                    'title' => $title,
                    'start' => $currentDay->setTime((int) $startTime->format('H'), (int) $startTime->format('i'))->format('c'),
                    'end' => $currentDay->setTime((int) $endTime->format('H'), (int) $endTime->format('i'))->format('c'),
                    'color' => '#b03030',
                ];

                $currentDay = $currentDay->modify('+1 day');
            }
        }

        return new JsonResponse($events);
    }
}