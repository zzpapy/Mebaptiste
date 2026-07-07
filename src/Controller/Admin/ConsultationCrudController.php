<?php

namespace App\Controller\Admin;

use App\Entity\Consultation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConsultationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Consultation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Type de consultation')
            ->setEntityLabelInPlural('Types de consultation')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield IntegerField::new('durationMinutes', 'Durée (minutes)');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield BooleanField::new('isActive', 'Actif');
    }
}