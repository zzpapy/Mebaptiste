<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use App\Repository\AvailabilityRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Symfony\Component\HttpFoundation\RequestStack;

class AvailabilityCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Availability::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Disponibilité')
            ->setEntityLabelInPlural('Disponibilités')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Récurrente' => Availability::TYPE_RECURRING,
                'Ponctuelle' => Availability::TYPE_PUNCTUAL,
            ]);
        yield ChoiceField::new('dayOfWeek', 'Jour')
            ->setChoices([
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 7,
            ])
            ->setHelp('Uniquement pour une disponibilité récurrente');
        yield DateField::new('date', 'Date')
            ->setHelp('Uniquement pour une disponibilité ponctuelle')
            ->hideOnIndex();
        yield TimeField::new('startTime', 'Heure de début')
            ->setFormat('HH:mm');
        yield TimeField::new('endTime', 'Heure de fin')
            ->setFormat('HH:mm');
        yield BooleanField::new('isActive', 'Actif');
    }

    private function hasOverlap(Availability $availability): bool
    {
        $overlapping = $this->availabilityRepository->findOverlapping($availability, $availability->getId());

        return count($overlapping) > 0;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($this->hasOverlap($entityInstance)) {
            $this->addFlash('danger', 'Cette disponibilité chevauche une disponibilité existante pour ce même jour/horaire.');

            return;
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($this->hasOverlap($entityInstance)) {
            $this->addFlash('danger', 'Cette disponibilité chevauche une disponibilité existante pour ce même jour/horaire.');

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}