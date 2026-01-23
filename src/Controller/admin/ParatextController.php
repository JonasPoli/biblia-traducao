<?php

namespace App\Controller\Admin;

use App\Entity\Paratext;
use App\Entity\User;
use App\Repository\ParatextRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/paratext')]
#[IsGranted('ROLE_USER')]
class ParatextController extends AbstractController
{
    #[Route('/', name: 'app_admin_paratext_index', methods: ['GET'])]
    public function index(ParatextRepository $paratextRepository): Response
    {
        return $this->render('admin/paratext/index.html.twig', [
            'paratexts' => $paratextRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_paratext_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
        $paratext = new Paratext();

        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $type = $request->request->get('type');
        $bookId = $request->request->get('book_id');
        $chapter = $request->request->get('chapter');

        if ($request->isMethod('POST')) {
            if (!$title || !$type) {
                $this->addFlash('error', 'Título e Tipo são obrigatórios.');
            } else {
                $paratext->setTitle($title);
                $paratext->setContent($content); // Raw HTML from TinyMCE
                $paratext->setType($type);

                if ($bookId) {
                    $paratext->setBook($bookRepository->find($bookId));
                }
                if ($chapter) {
                    $paratext->setChapter((int) $chapter);
                }

                $user = $this->getUser();
                if ($user instanceof User) {
                    $paratext->setAuthor($user);
                }

                $entityManager->persist($paratext);
                $entityManager->flush();

                return $this->redirectToRoute('app_admin_paratext_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/paratext/new.html.twig', [
            'paratext' => $paratext,
            'books' => $bookRepository->findBy([], ['bookOrder' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_paratext_show', methods: ['GET'])]
    public function show(Paratext $paratext, \App\Repository\UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $isAuthor = ($user instanceof User && $paratext->getAuthor() === $user);

        $recipients = [];
        // If not author, we can message the author.
        // Or if Admin, any one.
        // User requested: "Author cannot send comment on their own text".
        // Implies: If isAuthor is true, hide button.

        // Logic for recipients if button is shown:
        // Default: Send to Author.
        // If current user IS Author (shouldn't happen due to hide, but safety check), recipients empty.
        // If current user is NOT Author, Author should be in list.
        if (!$isAuthor && $paratext->getAuthor()) {
            $recipients[] = $paratext->getAuthor();
        }

        // Also allow sending to Admins? Or just Author?
        // Let's add Admins as fallback or for wider discussion.
        // Specs: "can only send to translators" (Reviewer context). 
        // For Paratext, likely we want to discuss with Author.

        return $this->render('admin/paratext/show.html.twig', [
            'paratext' => $paratext,
            'isAuthor' => $isAuthor,
            'recipients' => $recipients,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_paratext_edit', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    public function edit(Request $request, Paratext $paratext, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $type = $request->request->get('type');
            $bookId = $request->request->get('book_id');
            $chapter = $request->request->get('chapter');

            if (!$title || !$type) {
                $this->addFlash('error', 'Título e Tipo são obrigatórios.');
            } else {
                $paratext->setTitle($title);
                $paratext->setContent($content);
                $paratext->setType($type);

                if ($bookId) {
                    $paratext->setBook($bookRepository->find($bookId));
                } else {
                    $paratext->setBook(null);
                }

                if ($chapter) {
                    $paratext->setChapter((int) $chapter);
                } else {
                    $paratext->setChapter(null);
                }

                $paratext->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->flush();

                return $this->redirectToRoute('app_admin_paratext_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/paratext/edit.html.twig', [
            'paratext' => $paratext,
            'books' => $bookRepository->findBy([], ['bookOrder' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_paratext_delete', methods: ['POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    public function delete(Request $request, Paratext $paratext, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $paratext->getId(), $request->request->get('_token'))) {
            $entityManager->remove($paratext);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_paratext_index', [], Response::HTTP_SEE_OTHER);
    }
}
