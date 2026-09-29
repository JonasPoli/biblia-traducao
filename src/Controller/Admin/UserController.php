<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\AuthEmailService;
use App\Service\PasswordTokenService;
use App\Service\UserImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/import', name: 'app_admin_user_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        UserImportService $userImportService,
        AuthEmailService $authEmailService
    ): Response {
        $result = null;

        if ($request->isMethod('POST')) {
            $csrfToken = (string) $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('user_import', $csrfToken)) {
                $this->addFlash('error', 'Token de segurança inválido. Tente novamente.');
                return $this->redirectToRoute('app_admin_user_import');
            }

            $sendEmail = (bool) $request->request->get('send_email', false);
            $overwrite = (bool) $request->request->get('overwrite', false);
            $dryRun = (bool) $request->request->get('dry_run', false);

            $csvContent = '';
            $uploadedFile = $request->files->get('csv_file');
            $pastedContent = (string) $request->request->get('csv_content', '');

            if ($uploadedFile && $uploadedFile->isValid()) {
                $csvContent = file_get_contents($uploadedFile->getPathname());
            } elseif (trim($pastedContent) !== '') {
                $csvContent = $pastedContent;
            }

            if (empty(trim($csvContent))) {
                $this->addFlash('error', 'Por favor, envie um arquivo CSV ou cole o conteúdo de texto no campo.');
            } else {
                $rows = $userImportService->parseCsv($csvContent);
                if (empty($rows)) {
                    $this->addFlash('error', 'Nenhum registro válido pôde ser extraído do CSV.');
                } else {
                    $result = $userImportService->importUsers($rows, $sendEmail, $overwrite, $dryRun);
                    $this->addFlash($dryRun ? 'info' : 'success', sprintf(
                        $dryRun
                            ? 'Simulação: %d seriam criados, %d atualizados, %d já cadastrados, %d com erro. Nada foi gravado nem enviado.'
                            : 'Importação concluída! %d criados, %d atualizados, %d já cadastrados, %d com erro.',
                        count($result['created']),
                        count($result['updated']),
                        count($result['skipped']),
                        count($result['errors'])
                    ) . ($dryRun ? '' : sprintf(' %d e-mails enviados.', $result['emails_sent'])));
                }
            }
        }

        return $this->render('admin/user/import.html.twig', [
            'result' => $result,
            'groupLabels' => User::GROUP_LABELS,
            'invitationHours' => PasswordTokenService::INVITATION_TTL_HOURS,
        ]);
    }

    #[Route('/import/template', name: 'app_admin_user_import_template', methods: ['GET'])]
    public function importTemplate(): Response
    {
        $csvContent = "\xEF\xBB\xBF" . "Nome,Email,Grupo de Trabalho\n"
            . "João da Silva,joao.silva@exemplo.com,Criar paratextos\n"
            . "Maria Santos,maria.santos@exemplo.com,\"Criar paratextos, Conferir paratextos\"\n"
            . "Pedro Oliveira,pedro.oliveira@exemplo.com,Conferir tradução\n"
            . "Ana Costa,ana.costa@exemplo.com,\"Conferir tradução, Conferir paratextos\"\n"
            . "Carlos Tradutor,carlos@exemplo.com,Tradutor\n";

        $response = new Response($csvContent);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'modelo_importacao_usuarios.csv'
        );

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/{id}/send-reset-link', name: 'app_admin_user_send_reset_link', methods: ['POST'])]
    public function sendResetLink(
        Request $request,
        User $user,
        PasswordTokenService $passwordTokenService,
        AuthEmailService $authEmailService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('send_reset_' . $user->getId(), $request->request->get('_token'))) {
            // Convite pendente → novo convite (72h); senão → redefinição normal (2h)
            $type = $passwordTokenService->issueForUser($user);
            $entityManager->flush();

            $sent = $authEmailService->sendPasswordResetEmail($user, null, $type);
            if ($sent) {
                $this->addFlash('success', sprintf(
                    '%s enviado para %s (%s). Link válido por %dh.',
                    $type === PasswordTokenService::TYPE_INVITATION ? 'Convite' : 'E-mail de redefinição de senha',
                    $user->getName(),
                    $user->getEmail(),
                    $type === PasswordTokenService::TYPE_INVITATION ? PasswordTokenService::INVITATION_TTL_HOURS : PasswordTokenService::RESET_TTL_HOURS
                ));
            } else {
                $this->addFlash('error', sprintf('Falha ao enviar e-mail para %s (%s). Verifique as configurações de envio.', $user->getName(), $user->getEmail()));
            }
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, \Symfony\Component\String\Slugger\SluggerInterface $slugger, PasswordTokenService $passwordTokenService, AuthEmailService $authEmailService): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Image Upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $user->setImage($newFilename);
            }

            // encode the plain password
            if ($user->getPassword() === null) {
                // Generate random password if not provided (though form handles it, good fallback)
                // In real flow, we might send email reset here. 
            }

            $strPassword = $form->get('plainPassword')->getData();
            if ($strPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $strPassword
                    )
                );
                $user->setPasswordSetAt(new \DateTimeImmutable());
                $user->clearResetToken();
            }

            // Sem senha informada: cria como convite e envia o e-mail para a pessoa criar a senha (72h)
            $sendInvitation = !$strPassword;
            if ($sendInvitation) {
                $user->setPassword($userPasswordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                $user->setPasswordSetAt(null);
                $passwordTokenService->issueInvitationToken($user);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            if ($sendInvitation) {
                $sent = $authEmailService->sendPasswordResetEmail($user, null, PasswordTokenService::TYPE_INVITATION);
                $this->addFlash($sent ? 'success' : 'error', $sent
                    ? sprintf('Convite para criar a senha enviado para %s.', $user->getEmail())
                    : sprintf('Usuário criado, mas o e-mail para %s falhou. Use o botão de reenviar link.', $user->getEmail()));
            }

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Image Upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $user->setImage($newFilename);
            }

            $strPassword = $form->get('plainPassword')->getData();
            if ($strPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $strPassword
                    )
                );
                $user->setPasswordSetAt(new \DateTimeImmutable());
                $user->clearResetToken();
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
