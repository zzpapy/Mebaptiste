<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Consultation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultation $consultation = null;

    /**
     * Propriété virtuelle (non persistée) utilisée uniquement par le formulaire admin
     * pour saisir le type de consultation en texte libre. Résolue en une véritable
     * Consultation (existante ou nouvellement créée) dans AppointmentCrudController.
     */
    private ?string $consultationName = null;

    /**
     * Propriétés virtuelles (non persistées) utilisées uniquement par le formulaire
     * admin, pour saisir une date unique + heure de début/fin (le rendez-vous se
     * termine toujours le même jour). Combinées en startAt/endAt dans le contrôleur.
     */
    private ?\DateTimeInterface $appointmentDate = null;
    private ?\DateTimeInterface $startTimeOnly = null;
    private ?\DateTimeInterface $endTimeOnly = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\Column(length: 100)]
    private ?string $clientFirstName = null;

    #[ORM\Column(length: 100)]
    private ?string $clientLastName = null;

    #[ORM\Column(length: 180)]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $clientPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $cancelToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reminderSentAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;

        return $this;
    }

    public function getConsultationName(): ?string
    {
        return $this->consultationName ?? $this->consultation?->getName();
    }

    public function setConsultationName(?string $consultationName): static
    {
        $this->consultationName = $consultationName;

        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate ?? $this->startAt;
    }

    public function setAppointmentDate(?\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getStartTimeOnly(): ?\DateTimeInterface
    {
        return $this->startTimeOnly ?? $this->startAt;
    }

    public function setStartTimeOnly(?\DateTimeInterface $startTimeOnly): static
    {
        $this->startTimeOnly = $startTimeOnly;

        return $this;
    }

    public function getEndTimeOnly(): ?\DateTimeInterface
    {
        return $this->endTimeOnly ?? $this->endAt;
    }

    public function setEndTimeOnly(?\DateTimeInterface $endTimeOnly): static
    {
        $this->endTimeOnly = $endTimeOnly;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeInterface $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getClientFirstName(): ?string
    {
        return $this->clientFirstName;
    }

    public function setClientFirstName(string $clientFirstName): static
    {
        $this->clientFirstName = $clientFirstName;

        return $this;
    }

    public function getClientLastName(): ?string
    {
        return $this->clientLastName;
    }

    public function setClientLastName(string $clientLastName): static
    {
        $this->clientLastName = $clientLastName;

        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    public function setClientPhone(?string $clientPhone): static
    {
        $this->clientPhone = $clientPhone;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCancelToken(): ?string
    {
        return $this->cancelToken;
    }

    public function setCancelToken(?string $cancelToken): static
    {
        $this->cancelToken = $cancelToken;

        return $this;
    }

    public function getReminderSentAt(): ?\DateTimeInterface
    {
        return $this->reminderSentAt;
    }

    public function setReminderSentAt(?\DateTimeInterface $reminderSentAt): static
    {
        $this->reminderSentAt = $reminderSentAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}