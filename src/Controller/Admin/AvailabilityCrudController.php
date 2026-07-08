<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class AvailabilityCrudController extends AbstractCrudController
{
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
}