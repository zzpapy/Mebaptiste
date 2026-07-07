<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\AppointmentVerification;
use App\Repository\AppointmentVerificationRepository;
use App\Repository\ConsultationRepository;
use App\Service\SlotGeneratorService;
use App\Service\VerificationCodeMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    public function __construct(
        private readonly ConsultationRepository $consultationRepository,
        private readonly AppointmentVerificationRepository $verificationRepository,
        private readonly SlotGeneratorService $slotGeneratorService,
        private readonly VerificationCodeMailer $verificationCodeMailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Convertit une chaîne ISO (avec ou sans fuseau horaire) en DateTimeImmutable "naïf",
     * en ne gardant que la date et l'heure locale, pour rester cohérent avec le stockage
     * des disponibilités (elles aussi sans fuseau horaire).
     */
    private function parseNaiveDateTime(string $value): \DateTimeImmutable
    {
        // On ne garde que "YYYY-MM-DDTHH:MM:SS", on ignore un éventuel "+02:00" ou "Z" à la fin
        $truncated = substr($value, 0, 19);

        return new \DateTimeImmutable($truncated);
    }

    #[Route('/rendez-vous', name: 'booking_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('booking/index.html.twig', [
            'consultations' => $this->consultationRepository->findActive(),
        ]);
    }

    #[Route('/rendez-vous/creneaux', name: 'booking_slots', methods: ['GET'])]
    public function slots(Request $request): JsonResponse
    {
        $consultationId = $request->query->get('consultationId');
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!$consultationId || !$start || !$end) {
            return new JsonResponse(['error' => 'Paramètres manquants'], 400);
        }

        $consultation = $this->consultationRepository->find($consultationId);

        if (!$consultation || !$consultation->isActive()) {
            return new JsonResponse(['error' => 'Type de consultation invalide'], 404);
        }

        $startDate = $this->parseNaiveDateTime($start);
        $endDate = $this->parseNaiveDateTime($end);

        $slots = $this->slotGeneratorService->getAvailableSlots($consultation, $startDate, $endDate);

        $events = array_map(static fn (array $slot) => [
            'start' => $slot['start']->format('Y-m-d\TH:i:s'),
            'end' => $slot['end']->format('Y-m-d\TH:i:s'),
            'title' => 'Disponible',
            'display' => 'block',
        ], $slots);

        return new JsonResponse($events);
    }

    #[Route('/rendez-vous/demarrer', name: 'booking_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $consultationId = $request->request->get('consultationId');
        $start = $request->request->get('start');
        $end = $request->request->get('end');
        $firstName = trim((string) $request->request->get('firstName'));
        $lastName = trim((string) $request->request->get('lastName'));
        $email = trim((string) $request->request->get('email'));
        $phone = trim((string) $request->request->get('phone'));
        $message = trim((string) $request->request->get('message'));

        $consultation = $this->consultationRepository->find($consultationId);

        if (!$consultation || !$consultation->isActive()) {
            return new JsonResponse(['error' => 'Type de consultation invalide'], 404);
        }

        if ($firstName === '' || $lastName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Coordonnées invalides'], 400);
        }

        if (!$start || !$end) {
            return new JsonResponse(['error' => 'Créneau invalide'], 400);
        }

        $startDate = $this->parseNaiveDateTime($start);
        $endDate = $this->parseNaiveDateTime($end);

        $availableSlots = $this->slotGeneratorService->getAvailableSlots($consultation, $startDate, $endDate);
        $slotStillAvailable = false;
        foreach ($availableSlots as $slot) {
            if ($slot['start'] == $startDate && $slot['end'] == $endDate) {
                $slotStillAvailable = true;
                break;
            }
        }

        if (!$slotStillAvailable) {
            return new JsonResponse(['error' => 'Ce créneau n\'est plus disponible'], 409);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = new AppointmentVerification();
        $verification->setConsultation($consultation);
        $verification->setStartAt(\DateTime::createFromImmutable($startDate));
        $verification->setEndAt(\DateTime::createFromImmutable($endDate));
        $verification->setClientFirstName($firstName);
        $verification->setClientLastName($lastName);
        $verification->setClientEmail($email);
        $verification->setClientPhone($phone !== '' ? $phone : null);
        $verification->setMessage($message !== '' ? $message : null);
        $verification->setCode($code);
        $verification->setAttempts(0);
        $verification->setCreatedAt(new \DateTime());
        $verification->setExpiresAt(new \DateTime('+15 minutes'));

        $this->entityManager->persist($verification);
        $this->entityManager->flush();

        $this->verificationCodeMailer->send($verification);

        return new JsonResponse(['success' => true, 'verificationId' => $verification->getId()]);
    }

    #[Route('/rendez-vous/verifier', name: 'booking_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $verificationId = $request->request->get('verificationId');
        $code = trim((string) $request->request->get('code'));

        $verification = $this->verificationRepository->find($verificationId);

        if (!$verification) {
            return new JsonResponse(['error' => 'Demande introuvable, merci de recommencer.'], 404);
        }

        if ($verification->isExpired()) {
            $this->entityManager->remove($verification);
            $this->entityManager->flush();

            return new JsonResponse(['error' => 'Le code a expiré, merci de recommencer.'], 410);
        }

        if ($verification->getAttempts() >= 5) {
            $this->entityManager->remove($verification);
            $this->entityManager->flush();

            return new JsonResponse(['error' => 'Trop de tentatives, merci de recommencer.'], 429);
        }

        if ($code === '' || $code !== $verification->getCode()) {
            $verification->setAttempts($verification->getAttempts() + 1);
            $this->entityManager->flush();

            return new JsonResponse(['error' => 'Code incorrect.'], 400);
        }

        $consultation = $verification->getConsultation();

        $startDate = \DateTimeImmutable::createFromInterface($verification->getStartAt());
        $endDate = \DateTimeImmutable::createFromInterface($verification->getEndAt());

        // Revérification finale du créneau, au cas où il aurait été pris entre-temps.
        // On exclut la vérification en cours de validation elle-même, sinon elle se
        // bloque systématiquement en se détectant comme "créneau en attente".
        $availableSlots = $this->slotGeneratorService->getAvailableSlots(
            $consultation,
            $startDate,
            $endDate,
            $verification->getId()
        );
        $slotStillAvailable = false;
        foreach ($availableSlots as $slot) {
            if ($slot['start'] == $startDate && $slot['end'] == $endDate) {
                $slotStillAvailable = true;
                break;
            }
        }

        if (!$slotStillAvailable) {
            $this->entityManager->remove($verification);
            $this->entityManager->flush();

            return new JsonResponse(['error' => 'Ce créneau vient d\'être pris, merci de choisir un autre horaire.'], 409);
        }

        $appointment = new Appointment();
        $appointment->setConsultation($consultation);
        $appointment->setStartAt($verification->getStartAt());
        $appointment->setEndAt($verification->getEndAt());
        $appointment->setClientFirstName($verification->getClientFirstName());
        $appointment->setClientLastName($verification->getClientLastName());
        $appointment->setClientEmail($verification->getClientEmail());
        $appointment->setClientPhone($verification->getClientPhone());
        $appointment->setMessage($verification->getMessage());
        $appointment->setStatus(Appointment::STATUS_PENDING);
        $appointment->setCreatedAt(new \DateTime());

        $this->entityManager->persist($appointment);
        $this->entityManager->remove($verification);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}