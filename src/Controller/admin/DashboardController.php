<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\MessageRepository;
use App\Repository\ParatextRepository;
use App\Service\BibleDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    private const TARGET_VERSION_ID = 17; // Haroldo Dutra

    #[Route('/admin', name: 'app_dashboard')]
    public function index(
        BookRepository $bookRepository,
        BibleDataService $bibleDataService,
        ParatextRepository $paratextRepository,
        MessageRepository $messageRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Global Stats (Bible)
        $books = $bookRepository->getBooksWithProgress(self::TARGET_VERSION_ID);
        $globalStats = $bookRepository->getGlobalProgress(self::TARGET_VERSION_ID);
        $visuals = $bibleDataService->getVisualsMap();

        // Paratext Stats
        $paratextCount = $paratextRepository->count([]);

        // Messages (Unresolved/Unanswered)
        // Assuming 'new' and 'read' are 'unresolved'. 'answered', 'resolved', 'ignored' are resolved.
        // Or simply list all that are NOT resolved/ignored.
        // Method findByUser returns all. We can filter in Twig or create a custom repository method.
        // Let's rely on MessageRepository to fetch relevant messages.
        // User wants: "Mensagens não respondidas" (Count?) and "Lista das mensagens (menos as resolvidas)"

        $messages = $messageRepository->findByUser($user);
        $activeMessages = array_filter($messages, fn($m) => !in_array($m->getStatus(), ['resolved', 'ignored']));
        $unansweredCount = count(array_filter($messages, fn($m) => $m->getStatus() === 'new' && $m->getRecipient() === $user));

        // Paratext Lists based on Role
        $myParatexts = [];
        $recentParatexts = [];

        // Check Group/Role
        // Group 3: Author Paratext
        if ($user->getWorkGroup() === 3 || $user->getWorkGroup() === 0) {
            $myParatexts = $paratextRepository->findBy(['author' => $user], ['updatedAt' => 'DESC'], 5);
        }

        // Group 4: Reviewer Paratext (or Admin/Others)
        if ($user->getWorkGroup() === 4 || $user->getWorkGroup() === 0) {
            $recentParatexts = $paratextRepository->findBy([], ['updatedAt' => 'DESC'], 5);
        }

        return $this->render('dashboard/index.html.twig', [
            'books' => $books,
            'stats' => $globalStats,
            'visuals' => $visuals,
            'paratextCount' => $paratextCount,
            'messages' => $activeMessages,
            'unansweredCount' => $unansweredCount,
            'myParatexts' => $myParatexts,
            'recentParatexts' => $recentParatexts,
        ]);
    }

    #[Route('/admin/dashboard/book/{id}', name: 'app_dashboard_chapters')]
    public function chapters(int $id, BookRepository $bookRepository, BibleDataService $bibleDataService): Response
    {
        $book = $bookRepository->find($id);
        if (!$book) {
            throw $this->createNotFoundException('Book not found');
        }

        $chapters = $bookRepository->getChaptersWithProgress($id, self::TARGET_VERSION_ID);
        $visuals = $bibleDataService->getVisualsMap();
        $relatedBooks = $bookRepository->findBy(['testament' => $book->getTestament()], ['bookOrder' => 'ASC']);

        return $this->render('dashboard/chapters.html.twig', [
            'book' => $book,
            'chapters' => $chapters,
            'visuals' => $visuals,
            'relatedBooks' => $relatedBooks,
        ]);
    }
}
