<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            return $this->render('security/reset_password.html.twig', [
                'tokenValid' => false,
                'user' => null,
                'error' => 'O link de definição de senha é inválido ou já expirou. Solicite um novo convite ao administrador.',
            ]);
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');
            $confirmPassword = (string) $request->request->get('confirm_password');
            $csrfToken = (string) $request->request->get('_csrf_token');

            if (!$this->isCsrfTokenValid('reset_password_' . $token, $csrfToken)) {
                $error = 'Token de segurança inválido. Por favor, tente novamente.';
            } elseif (strlen($password) < 6) {
                $error = 'A senha deve conter no mínimo 6 caracteres.';
            } elseif ($password !== $confirmPassword) {
                $error = 'As senhas informadas não coincidem. Digite a mesma senha nos dois campos.';
            } else {
                // Save new password and invalidate token
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->clearResetToken();
                $entityManager->flush();

                $this->addFlash('success', 'Sua senha foi cadastrada com sucesso! Você já pode fazer login.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'tokenValid' => true,
            'user' => $user,
            'token' => $token,
            'error' => $error,
        ]);
    }
}
