<?php

namespace App\Service;

use App\Entity\AppointmentVerification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class VerificationCodeMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function send(AppointmentVerification $verification): void
    {
        $email = (new Email())
            ->from('noreply@lebrou-avocat.fr')
            ->to($verification->getClientEmail())
            ->subject('Votre code de vérification - Prise de rendez-vous')
            ->html($this->buildHtml($verification));

        $this->mailer->send($email);
    }

    private function buildHtml(AppointmentVerification $verification): string
    {
        $code = htmlspecialchars($verification->getCode());
        $consultationName = htmlspecialchars($verification->getConsultation()?->getName() ?? '');
        $start = $verification->getStartAt()->format('d/m/Y H:i');

        return <<<HTML
            <div style="font-family: sans-serif; max-width: 480px; margin: 0 auto;">
                <h2 style="color: #1a3a5c;">Confirmez votre demande de rendez-vous</h2>
                <p>Vous avez demandé un rendez-vous pour <strong>{$consultationName}</strong> le <strong>{$start}</strong>.</p>
                <p>Voici votre code de vérification. Il est valable 15 minutes.</p>
                <div style="background: #f0f0f0; padding: 16px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 8px; border-radius: 6px;">
                    {$code}
                </div>
                <p style="margin-top: 24px; font-size: 13px; color: #666;">
                    Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                </p>
            </div>
        HTML;
    }
}