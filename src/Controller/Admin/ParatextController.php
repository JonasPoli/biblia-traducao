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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/paratext')]
#[IsGranted('ROLE_USER')]
class ParatextController extends AbstractController
{
    #[Route('/', name: 'app_admin_paratext_index', methods: ['GET'])]
    public function index(ParatextRepository $paratextRepository, \App\Repository\ParatextReviewRepository $paratextReviewRepository): Response
    {
        $paratexts = $paratextRepository->findAll();

        // Fetch Reviews
        $user = $this->getUser();
        $userReviews = [];
        $reviewCounts = [];
        
        // Fetch all reviews
        $reviews = $paratextReviewRepository->findAll();
        
        foreach ($reviews as $review) {
            $ptId = $review->getParatext()->getId();
            
            if ($user && $review->getUser() === $user) {
                $userReviews[$ptId] = true;
            }
            
            if (!isset($reviewCounts[$ptId])) {
                $reviewCounts[$ptId] = 0;
            }
            $reviewCounts[$ptId]++;
        }

        return $this->render('admin/paratext/index.html.twig', [
            'paratexts' => $paratexts,
            'userReviews' => $userReviews,
            'reviewCounts' => $reviewCounts,
        ]);
    }

    #[Route('/new', name: 'app_admin_paratext_new', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, SluggerInterface $slugger, \App\Repository\VerseRepository $verseRepository): Response
    {
        $paratext = new Paratext();

        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $type = $request->request->get('type');
        $bookId = $request->request->get('book_id');
        $chapter = $request->request->get('chapter');
        $verse = $request->request->get('verse');

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
                if ($verse) {
                    $paratext->setVerse((int) $verse);
                }

                $user = $this->getUser();
                if ($user instanceof User) {
                    $paratext->setAuthor($user);
                }

                // Image Upload
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/paratext',
                            $newFilename
                        );
                        $paratext->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erro ao fazer upload da imagem.');
                    }
                }

                $entityManager->persist($paratext);
                $entityManager->flush();

                return $this->redirectToRoute('app_admin_paratext_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        
        // Fetch Bible Structure for JS
        $structure = [];
        // Simplified structure: [book_id => max_chapters]
        // Actually user needs max_verses per chapter. 
        // This query might be heavy if not careful.
        // Let's optimize: fetch all verses counts grouped by book and chapter.
        
        $conn = $entityManager->getConnection();
        // SQLite/MySQL compatible
        $sql = 'SELECT book_id, chapter, MAX(verse) as max_verse FROM verse GROUP BY book_id, chapter';
        $rows = $conn->fetchAllAssociative($sql);
        
        $bibleStructure = [];
        foreach ($rows as $row) {
             $bibleStructure[$row['book_id']]['chapters'][$row['chapter']] = $row['max_verse'];
        }
        
        // Also need max chapters per book? We can deduce it from the keys of chapters.

        return $this->render('admin/paratext/new.html.twig', [
            'paratext' => $paratext,
            'books' => $bookRepository->findBy([], ['bookOrder' => 'ASC']),
            'bibleStructure' => $bibleStructure,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_paratext_show', methods: ['GET'])]
    public function show(Paratext $paratext, \App\Repository\UserRepository $userRepository, \App\Repository\ParatextReviewRepository $paratextReviewRepository): Response
    {
        $user = $this->getUser();
        $isAuthor = ($user instanceof User && $paratext->getAuthor() === $user);
        
        $reviews = $paratextReviewRepository->findBy(['paratext' => $paratext], ['createdAt' => 'DESC']);
        $isReviewed = $user ? (bool) $paratextReviewRepository->findOneBy(['user' => $user, 'paratext' => $paratext]) : false;

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
            'reviews' => $reviews,
            'isReviewed' => $isReviewed,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_paratext_edit', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    #[Route('/{id}/edit', name: 'app_admin_paratext_edit', methods: ['GET', 'POST'])]
    #[IsGranted('CAN_EDIT_PARATEXT')]
    public function edit(Request $request, Paratext $paratext, EntityManagerInterface $entityManager, BookRepository $bookRepository, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $type = $request->request->get('type');
            $bookId = $request->request->get('book_id');
            $chapter = $request->request->get('chapter');
            $verse = $request->request->get('verse');

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

                if ($verse) {
                    $paratext->setVerse((int) $verse);
                } else {
                    $paratext->setVerse(null);
                }

                // Image Upload
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/paratext',
                            $newFilename
                        );
                        // Delete old image if exists? Optional but good practice.
                        $paratext->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erro ao fazer upload da imagem.');
                    }
                }
                
                $paratext->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->flush();

                return $this->redirectToRoute('app_admin_paratext_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        
        $conn = $entityManager->getConnection();
        $sql = 'SELECT book_id, chapter, MAX(verse) as max_verse FROM verse GROUP BY book_id, chapter';
        $rows = $conn->fetchAllAssociative($sql);
        
        $bibleStructure = [];
        foreach ($rows as $row) {
             $bibleStructure[$row['book_id']]['chapters'][$row['chapter']] = $row['max_verse'];
        }

        return $this->render('admin/paratext/edit.html.twig', [
            'paratext' => $paratext,
            'books' => $bookRepository->findBy([], ['bookOrder' => 'ASC']),
            'bibleStructure' => $bibleStructure,
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
