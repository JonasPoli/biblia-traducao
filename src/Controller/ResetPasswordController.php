<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthEmailService;
use App\Service\PasswordTokenService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    /**
     * "Esqueci minha senha": envia um link de redefinição válido por 2h
     * (ou um novo convite de 72h, se a pessoa ainda não tinha criado a senha).
     * A resposta é sempre a mesma, para não revelar quais e-mails estão cadastrados.
     */
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PasswordTokenService $passwordTokenService,
        AuthEmailService $authEmailService,
        LoggerInterface $logger
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            $identifier = trim((string) $request->request->get('email'));

            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
                $error = 'Token de segurança inválido. Por favor, tente novamente.';
            } elseif ($identifier === '') {
                $error = 'Informe o seu e-mail.';
            } else {
                $user = $userRepository->findOneByEmailOrUsername($identifier);

                if ($user && $user->getEmail() && !$passwordTokenService->wasIssuedRecently($user)) {
                    $type = $passwordTokenService->issueForUser($user);
                    $entityManager->flush();
                    $authEmailService->sendPasswordResetEmail($user, null, $type);
                } elseif ($user) {
                    $logger->info('Forgot-password request throttled or user without e-mail', ['userId' => $user->getId()]);
                }

                return $this->render('security/forgot_password.html.twig', [
                    'sent' => true,
                    'error' => null,
                    'resetHours' => PasswordTokenService::RESET_TTL_HOURS,
                ]);
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'sent' => false,
            'error' => $error,
            'resetHours' => PasswordTokenService::RESET_TTL_HOURS,
        ]);
    }

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
                'error' => 'O link de definição de senha é inválido ou já expirou. Use "Esqueci minha senha" para receber um novo link.',
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
                $user->setPasswordSetAt(new \DateTimeImmutable());
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
