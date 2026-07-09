<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
class ChangePasswordController extends AbstractController
{
    #[Route('/admin/mon-compte/mot-de-passe', name: 'admin_change_password', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $token = new CsrfToken('change_password', (string) $request->request->get('_csrf_token'));

            if (!$csrfTokenManager->isTokenValid($token)) {
                $error = 'Jeton de sécurité invalide, merci de réessayer.';
            } else {
                $currentPassword = (string) $request->request->get('current_password');
                $newPassword = (string) $request->request->get('new_password');
                $confirmPassword = (string) $request->request->get('confirm_password');

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $error = 'Le mot de passe actuel est incorrect.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Les deux mots de passe ne correspondent pas.';
                } else {
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    $entityManager->flush();
                    $success = true;
                }
            }
        }

        return $this->render('admin/change_password.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }
}