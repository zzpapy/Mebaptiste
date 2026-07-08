<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use App\Service\AppointmentConfirmationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-appointment-reminders',
    description: 'Envoie un email de rappel pour les rendez-vous ayant lieu dans les prochaines 24h.',
)]
class SendAppointmentRemindersCommand extends Command
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentConfirmationMailer $appointmentConfirmationMailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();
        $from = (clone $now)->modify('+23 hours');
        $to = (clone $now)->modify('+25 hours');

        $appointments = $this->appointmentRepository->findNeedingReminder($from, $to);

        foreach ($appointments as $appointment) {
            $this->appointmentConfirmationMailer->sendReminder($appointment);
            $appointment->setReminderSentAt(new \DateTime());
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('%d rappel(s) envoyé(s).', count($appointments)));

        return Command::SUCCESS;
    }
}