<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\AppointmentVerification;
use App\Entity\Availability;
use App\Entity\Blocage;
use App\Entity\Consultation;
use App\Repository\AppointmentRepository;
use App\Repository\AppointmentVerificationRepository;
use App\Repository\AvailabilityRepository;
use App\Repository\BlocageRepository;

class SlotGeneratorService
{
    public function __construct(
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentVerificationRepository $appointmentVerificationRepository,
        private readonly BlocageRepository $blocageRepository,
    ) {
    }

    /**
     * Génère les créneaux disponibles entre deux dates. Le type de consultation
     * n'a plus d'influence sur le calcul (c'est la disponibilité définie par
     * l'admin qui fixe la durée du créneau), il est conservé en paramètre
     * uniquement pour compatibilité, et peut être omis.
     *
     * @param int|null $excludeVerificationId Identifiant d'une AppointmentVerification à exclure
     *                                         du calcul (utilisé lors de la revérification finale
     *                                         dans BookingController::verify(), pour ne pas que la
     *                                         vérification en cours de validation se bloque elle-même).
     *
     * @return array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    public function getAvailableSlots(
        ?Consultation $consultation,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeVerificationId = null
    ): array {
        $recurringAvailabilities = $this->availabilityRepository->findActiveRecurring();
        $punctualAvailabilities = $this->availabilityRepository->findActivePunctualBetween($start, $end);
        $existingAppointments = $this->appointmentRepository->findActiveBetween($start, $end);
        $pendingVerifications = $this->appointmentVerificationRepository->findActiveBetween($start, $end);
        $blocages = $this->blocageRepository->findOverlapping($start, $end);

        if ($excludeVerificationId !== null) {
            $pendingVerifications = array_filter(
                $pendingVerifications,
                static fn (AppointmentVerification $verification) => $verification->getId() !== $excludeVerificationId
            );
        }

        $slots = [];

        $currentDay = \DateTimeImmutable::createFromInterface($start)->setTime(0, 0);
        $lastDay = \DateTimeImmutable::createFromInterface($end)->setTime(0, 0);
        $now = new \DateTimeImmutable();

        while ($currentDay <= $lastDay) {
            $dayOfWeek = (int) $currentDay->format('N'); // 1 = lundi ... 7 = dimanche

            $windowsForDay = [];

            foreach ($recurringAvailabilities as $availability) {
                if ($availability->getDayOfWeek() === $dayOfWeek) {
                    $windowsForDay[] = $this->buildWindow($currentDay, $availability);
                }
            }

            foreach ($punctualAvailabilities as $availability) {
                $availabilityDate = $availability->getDate();
                if ($availabilityDate !== null && $availabilityDate->format('Y-m-d') === $currentDay->format('Y-m-d')) {
                    $windowsForDay[] = $this->buildWindow($currentDay, $availability);
                }
            }

            $blocagesForDay = $this->buildBlocageWindowsForDay($currentDay, $blocages);

            foreach ($windowsForDay as $window) {
                $slot = $this->buildSlotFromWindow(
                    $window['start'],
                    $window['end'],
                    $existingAppointments,
                    $pendingVerifications,
                    $blocagesForDay,
                    $now
                );

                if ($slot !== null) {
                    $slots[] = $slot;
                }
            }

            $currentDay = $currentDay->modify('+1 day');
        }

        usort($slots, static fn (array $a, array $b) => $a['start'] <=> $b['start']);

        return $slots;
    }

    private function buildWindow(\DateTimeImmutable $day, Availability $availability): array
    {
        $startTime = $availability->getStartTime();
        $endTime = $availability->getEndTime();

        $start = $day->setTime((int) $startTime->format('H'), (int) $startTime->format('i'));
        $end = $day->setTime((int) $endTime->format('H'), (int) $endTime->format('i'));

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Construit, pour un jour donné, la liste des plages horaires bloquées par des Blocage
     * qui couvrent ce jour (journée entière ou plage horaire précise).
     *
     * @param Blocage[] $blocages
     *
     * @return array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    private function buildBlocageWindowsForDay(\DateTimeImmutable $day, array $blocages): array
    {
        $windows = [];

        foreach ($blocages as $blocage) {
            $blocageStart = \DateTimeImmutable::createFromInterface($blocage->getStartDate())->setTime(0, 0);
            $blocageEnd = \DateTimeImmutable::createFromInterface($blocage->getEndDate())->setTime(0, 0);

            if ($day < $blocageStart || $day > $blocageEnd) {
                continue;
            }

            if ($blocage->isFullDay()) {
                $windows[] = [
                    'start' => $day->setTime(0, 0),
                    'end' => $day->modify('+1 day')->setTime(0, 0),
                ];

                continue;
            }

            $startTime = $blocage->getStartTime();
            $endTime = $blocage->getEndTime();

            $windows[] = [
                'start' => $day->setTime((int) $startTime->format('H'), (int) $startTime->format('i')),
                'end' => $day->setTime((int) $endTime->format('H'), (int) $endTime->format('i')),
            ];
        }

        return $windows;
    }

    /**
     * Transforme une fenêtre de disponibilité (ex: 17h-18h définie en admin) en un
     * unique créneau réservable en un bloc, sans découpage automatique par durée.
     *
     * @param Appointment[] $existingAppointments
     * @param AppointmentVerification[] $pendingVerifications
     * @param array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $blocageWindows
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}|null
     */
    private function buildSlotFromWindow(
        \DateTimeImmutable $windowStart,
        \DateTimeImmutable $windowEnd,
        array $existingAppointments,
        array $pendingVerifications,
        array $blocageWindows,
        \DateTimeImmutable $now
    ): ?array {
        if ($windowStart <= $now
            || $this->isSlotTaken($windowStart, $windowEnd, $existingAppointments)
            || $this->isSlotPendingVerification($windowStart, $windowEnd, $pendingVerifications)
            || $this->isSlotBlocked($windowStart, $windowEnd, $blocageWindows)
        ) {
            return null;
        }

        return ['start' => $windowStart, 'end' => $windowEnd];
    }

    /**
     * @param Appointment[] $existingAppointments
     */
    private function isSlotTaken(\DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, array $existingAppointments): bool
    {
        foreach ($existingAppointments as $appointment) {
            $appointmentStart = \DateTimeImmutable::createFromInterface($appointment->getStartAt());
            $appointmentEnd = \DateTimeImmutable::createFromInterface($appointment->getEndAt());

            if ($slotStart < $appointmentEnd && $slotEnd > $appointmentStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param AppointmentVerification[] $pendingVerifications
     */
    private function isSlotPendingVerification(\DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, array $pendingVerifications): bool
    {
        foreach ($pendingVerifications as $verification) {
            $verificationStart = \DateTimeImmutable::createFromInterface($verification->getStartAt());
            $verificationEnd = \DateTimeImmutable::createFromInterface($verification->getEndAt());

            if ($slotStart < $verificationEnd && $slotEnd > $verificationStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable}> $blocageWindows
     */
    private function isSlotBlocked(\DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, array $blocageWindows): bool
    {
        foreach ($blocageWindows as $window) {
            if ($slotStart < $window['end'] && $slotEnd > $window['start']) {
                return true;
            }
        }

        return false;
    }
}