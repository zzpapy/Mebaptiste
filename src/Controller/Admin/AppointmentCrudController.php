<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Service\ConsultationResolver;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class AppointmentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ConsultationResolver $consultationResolver,
    ) {
    }

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

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('appointment_consultation_autocomplete');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('consultationName', 'Type de consultation')
            ->setHelp('Choisissez un type existant dans la liste déroulante ou tapez un nouveau nom : il sera créé automatiquement.');
        yield DateField::new('appointmentDate', 'Date')
            ->setFormat('dd/MM/yyyy')
            ->onlyOnForms();
        yield TimeField::new('startTimeOnly', 'Heure de début')
            ->onlyOnForms();
        yield TimeField::new('endTimeOnly', 'Heure de fin')
            ->onlyOnForms();
        yield DateField::new('startAt', 'Date')
            ->setFormat('dd/MM/yyyy')
            ->onlyOnIndex();
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

    private function combineDateAndAppointment(Appointment $appointment): void
    {
        $date = $appointment->getAppointmentDate();
        $startTime = $appointment->getStartTimeOnly();
        $endTime = $appointment->getEndTimeOnly();

        if ($date === null || $startTime === null || $endTime === null) {
            return;
        }

        $startAt = (new \DateTime())
            ->setDate((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))
            ->setTime((int) $startTime->format('H'), (int) $startTime->format('i'));

        $endAt = (new \DateTime())
            ->setDate((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))
            ->setTime((int) $endTime->format('H'), (int) $endTime->format('i'));

        $appointment->setStartAt($startAt);
        $appointment->setEndAt($endAt);
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof Appointment) {
            if ($entityInstance->getCreatedAt() === null) {
                $entityInstance->setCreatedAt(new \DateTime());
            }

            $this->combineDateAndAppointment($entityInstance);

            $consultation = $this->consultationResolver->resolveOrCreate((string) $entityInstance->getConsultationName());
            $entityInstance->setConsultation($consultation);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof Appointment) {
            $this->combineDateAndAppointment($entityInstance);

            $consultation = $this->consultationResolver->resolveOrCreate((string) $entityInstance->getConsultationName());
            $entityInstance->setConsultation($consultation);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}