<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AppointmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rendez-vous')
            ->setEntityLabelInPlural('Rendez-vous')
            ->setDefaultSort(['startAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('consultation', 'Type de consultation');
        yield DateTimeField::new('startAt', 'Début')
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('endAt', 'Fin')
            ->setFormat('dd/MM/yyyy HH:mm');
        yield TextField::new('clientFirstName', 'Prénom');
        yield TextField::new('clientLastName', 'Nom');
        yield TextField::new('clientEmail', 'Email');
        yield TextField::new('clientPhone', 'Téléphone')->hideOnIndex();
        yield TextareaField::new('message', 'Message')->hideOnIndex();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'Confirmé' => 'confirmed',
                'Annulé' => 'cancelled',
            ]);
    }
}