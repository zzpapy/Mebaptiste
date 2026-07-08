<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppointmentConfirmationMailer
{
    private const LAWYER_EMAIL = 'baptiste@lebrou-avocat.fr';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendToClient(Appointment $appointment): void
    {
        $email = (new Email())
            ->from('noreply@lebrou-avocat.fr')
            ->to($appointment->getClientEmail())
            ->subject('Confirmation de votre rendez-vous')
            ->html($this->buildClientHtml($appointment));

        $this->mailer->send($email);
    }

    public function sendToLawyer(Appointment $appointment): void
    {
        $email = (new Email())
            ->from('noreply@lebrou-avocat.fr')
            ->to(self::LAWYER_EMAIL)
            ->subject('Nouveau rendez-vous confirmé')
            ->html($this->buildLawyerHtml($appointment));

        $this->mailer->send($email);
    }

    public function sendCancellationToLawyer(Appointment $appointment): void
    {
        $email = (new Email())
            ->from('noreply@lebrou-avocat.fr')
            ->to(self::LAWYER_EMAIL)
            ->subject('Rendez-vous annulé par le client')
            ->html($this->buildCancellationHtml($appointment));

        $this->mailer->send($email);
    }

    private function buildCancelUrl(Appointment $appointment): string
    {
        return $this->urlGenerator->generate(
            'booking_cancel',
            ['token' => $appointment->getCancelToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function buildClientHtml(Appointment $appointment): string
    {
        $consultationName = htmlspecialchars($appointment->getConsultation()?->getName() ?? '');
        $start = $appointment->getStartAt()->format('d/m/Y H:i');
        $end = $appointment->getEndAt()->format('H:i');
        $firstName = htmlspecialchars($appointment->getClientFirstName());
        $cancelUrl = $this->buildCancelUrl($appointment);

        return <<<HTML
            <div style="font-family: sans-serif; max-width: 480px; margin: 0 auto;">
                <h2 style="color: #1a3a5c;">Votre rendez-vous est confirmé</h2>
                <p>Bonjour {$firstName},</p>
                <p>Votre rendez-vous pour <strong>{$consultationName}</strong> est bien confirmé :</p>
                <p style="background: #f0f0f0; padding: 16px; border-radius: 6px;">
                    Le <strong>{$start}</strong> à <strong>{$end}</strong>
                </p>
                <p style="margin-top: 24px;">
                    Un empêchement ? <a href="{$cancelUrl}" style="color: #1a3a5c;">Annuler ce rendez-vous</a>
                </p>
                <p style="margin-top: 24px; font-size: 13px; color: #666;">
                    Maître Baptiste Lebrou - Avocat au barreau de Strasbourg
                </p>
            </div>
        HTML;
    }

    private function buildLawyerHtml(Appointment $appointment): string
    {
        $consultationName = htmlspecialchars($appointment->getConsultation()?->getName() ?? '');
        $start = $appointment->getStartAt()->format('d/m/Y H:i');
        $end = $appointment->getEndAt()->format('H:i');
        $firstName = htmlspecialchars($appointment->getClientFirstName());
        $lastName = htmlspecialchars($appointment->getClientLastName());
        $clientEmail = htmlspecialchars($appointment->getClientEmail());
        $clientPhone = htmlspecialchars($appointment->getClientPhone() ?? 'non renseigné');
        $message = htmlspecialchars($appointment->getMessage() ?? '');

        return <<<HTML
            <div style="font-family: sans-serif; max-width: 480px; margin: 0 auto;">
                <h2 style="color: #1a3a5c;">Nouveau rendez-vous confirmé</h2>
                <p><strong>{$consultationName}</strong></p>
                <p>Le <strong>{$start}</strong> à <strong>{$end}</strong></p>
                <hr>
                <p><strong>Client :</strong> {$firstName} {$lastName}</p>
                <p><strong>Email :</strong> {$clientEmail}</p>
                <p><strong>Téléphone :</strong> {$clientPhone}</p>
                {$this->renderMessageBlock($message)}
            </div>
        HTML;
    }

    private function buildCancellationHtml(Appointment $appointment): string
    {
        $consultationName = htmlspecialchars($appointment->getConsultation()?->getName() ?? '');
        $start = $appointment->getStartAt()->format('d/m/Y H:i');
        $end = $appointment->getEndAt()->format('H:i');
        $firstName = htmlspecialchars($appointment->getClientFirstName());
        $lastName = htmlspecialchars($appointment->getClientLastName());

        return <<<HTML
            <div style="font-family: sans-serif; max-width: 480px; margin: 0 auto;">
                <h2 style="color: #b03030;">Rendez-vous annulé</h2>
                <p><strong>{$firstName} {$lastName}</strong> a annulé son rendez-vous :</p>
                <p><strong>{$consultationName}</strong></p>
                <p>Le <strong>{$start}</strong> à <strong>{$end}</strong></p>
                <p style="margin-top: 16px; font-size: 13px; color: #666;">
                    Ce créneau est de nouveau disponible à la réservation.
                </p>
            </div>
        HTML;
    }

    private function renderMessageBlock(string $message): string
    {
        if ($message === '') {
            return '';
        }

        return "<p><strong>Message :</strong><br>{$message}</p>";
    }
}