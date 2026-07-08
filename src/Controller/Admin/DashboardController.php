<?php

namespace App\Controller\Admin;

use App\Repository\AppointmentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'upcomingAppointments' => $this->appointmentRepository->findUpcoming(5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cabinet Lebrou - Administration')
            ->setFaviconPath('favicon.svg');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');

        yield MenuItem::section('Site');
        yield MenuItem::linkTo(PageCrudController::class, 'Pages', 'fas fa-file')->setAction(Action::INDEX);
        yield MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fas fa-newspaper')->setAction(Action::INDEX);

        yield MenuItem::section('Prise de rendez-vous');
        yield MenuItem::linkToRoute('Agenda', 'fas fa-calendar-alt', 'admin_agenda');
        yield MenuItem::linkTo(AppointmentCrudController::class, 'Rendez-vous', 'fas fa-address-book')->setAction(Action::INDEX);
        yield MenuItem::linkTo(ConsultationCrudController::class, 'Types de consultation', 'fas fa-stethoscope')->setAction(Action::INDEX);
        yield MenuItem::linkTo(AvailabilityCrudController::class, 'Disponibilités', 'fas fa-calendar-check')->setAction(Action::INDEX);
        yield MenuItem::linkTo(BlocageCrudController::class, 'Blocages (vacances, absences)', 'fas fa-ban')->setAction(Action::INDEX);

        yield MenuItem::section('Liens utiles');
        yield MenuItem::linkToRoute('Page publique de réservation', 'fas fa-external-link-alt', 'booking_index')->setLinkTarget('_blank');
    }
}