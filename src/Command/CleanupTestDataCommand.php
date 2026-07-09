<?php

namespace App\Command;

use App\Entity\Appointment;
use App\Entity\AppointmentVerification;
use App\Entity\Article;
use App\Entity\Availability;
use App\Entity\Blocage;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:cleanup-test-data', description: 'Supprime les RDV, disponibilités, blocages et pages/articles de test avant mise en ligne')]
class CleanupTestDataCommand extends Command
{
    // Slugs des pages à conserver — tout le reste sera supprimé.
    private const PAGES_TO_KEEP = [
        'accueil',
        'a-propos',
        'domaines-dexpertise',
        'mentions-legales',
        'politique-de-confidentialite',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $appointments = $this->em->getRepository(Appointment::class)->findAll();
        $verifications = $this->em->getRepository(AppointmentVerification::class)->findAll();
        $availabilities = $this->em->getRepository(Availability::class)->findAll();
        $blocages = $this->em->getRepository(Blocage::class)->findAll();

        $pagesToDelete = array_filter(
            $this->em->getRepository(Page::class)->findAll(),
            fn (Page $page) => !in_array($page->getSlug(), self::PAGES_TO_KEEP, true)
        );

        $articlesToDelete = $this->em->getRepository(Article::class)->findAll();

        $io->title('Nettoyage des données de test');
        $io->table(
            ['Type', 'Nombre à supprimer'],
            [
                ['Rendez-vous', count($appointments)],
                ['Vérifications en attente', count($verifications)],
                ['Disponibilités', count($availabilities)],
                ['Blocages', count($blocages)],
                ['Pages hors liste conservée', count($pagesToDelete)],
                ['Articles', count($articlesToDelete)],
            ]
        );

        $io->writeln('Pages conservées : ' . implode(', ', self::PAGES_TO_KEEP));
        $io->writeln('');
        $io->warning('Les Types de consultation ne sont PAS supprimés par cette commande.');

        $question = new ConfirmationQuestion('Confirmer la suppression ci-dessus ? (yes/no) ', false);
        if (!$io->askQuestion($question)) {
            $io->writeln('Annulé, rien n\'a été supprimé.');

            return Command::SUCCESS;
        }

        foreach ($appointments as $entity) {
            $this->em->remove($entity);
        }
        foreach ($verifications as $entity) {
            $this->em->remove($entity);
        }
        foreach ($availabilities as $entity) {
            $this->em->remove($entity);
        }
        foreach ($blocages as $entity) {
            $this->em->remove($entity);
        }
        foreach ($pagesToDelete as $entity) {
            $this->em->remove($entity);
        }
        foreach ($articlesToDelete as $entity) {
            $this->em->remove($entity);
        }

        $this->em->flush();

        $io->success('Nettoyage terminé.');

        return Command::SUCCESS;
    }
}
