<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Service\AvatarUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Painel "Meu Perfil": cada usuário mantém os próprios dados.
 */
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/admin/perfil', name: 'app_profile', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AvatarUploader $avatarUploader,
        Security $security,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messages = ['Seus dados foram atualizados.'];

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                if (!$avatarUploader->replace($user, $imageFile)) {
                    $this->addFlash('error', 'Não foi possível salvar a foto. Tente novamente.');
                }
            } elseif ($form->get('removeImage')->getData()) {
                $avatarUploader->remove($user);
            }

            // Só troca a senha se o usuário digitou uma nova (a senha atual já foi validada no form)
            $newPassword = (string) $form->get('newPassword')->getData();
            $passwordChanged = $newPassword !== '';
            if ($passwordChanged) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $user->setPasswordSetAt(new \DateTimeImmutable());
                $user->clearResetToken();
                $messages[] = 'Sua senha foi alterada.';
            }

            $entityManager->flush();

            if ($passwordChanged) {
                // Com o hash novo a sessão antiga seria invalidada; renova o login para não derrubar o usuário
                $security->login($user, 'form_login', 'main');
            }

            $this->addFlash('success', implode(' ', $messages));

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted()) {
            // Formulário inválido: descarta alterações em memória (nome no menu etc.)
            $entityManager->refresh($user);
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
