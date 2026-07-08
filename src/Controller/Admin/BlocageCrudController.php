<?php

namespace App\Controller\Admin;

use App\Entity\Blocage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class BlocageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Blocage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Blocage')
            ->setEntityLabelInPlural('Blocages')
            ->setDefaultSort(['startDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateField::new('startDate', 'Du');
        yield DateField::new('endDate', 'Au');
        yield TimeField::new('startTime', 'Heure de début')
            ->setFormat('HH:mm')
            ->setHelp('Laisser vide pour bloquer la/les journée(s) entière(s)')
            ->setRequired(false);
        yield TimeField::new('endTime', 'Heure de fin')
            ->setFormat('HH:mm')
            ->setHelp('Laisser vide pour bloquer la/les journée(s) entière(s)')
            ->setRequired(false);
        yield TextField::new('reason', 'Motif')
            ->setRequired(false)
            ->hideOnIndex();
    }
}