<?php

namespace App\Controller\admin;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    private const TARGET_VERSION_ID = 17; // Haroldo Dutra

    #[Route('/admin', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->getBooksWithProgress(self::TARGET_VERSION_ID);
        $globalStats = $bookRepository->getGlobalProgress(self::TARGET_VERSION_ID);

        return $this->render('dashboard/index.html.twig', [
            'books' => $books,
            'stats' => $globalStats,
        ]);
    }

    #[Route('/admin/dashboard/book/{id}', name: 'app_dashboard_chapters')]
    public function chapters(int $id, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);
        if (!$book) {
            throw $this->createNotFoundException('Book not found');
        }

        $chapters = $bookRepository->getChaptersWithProgress($id, self::TARGET_VERSION_ID);

        return $this->render('dashboard/chapters.html.twig', [
            'book' => $book,
            'chapters' => $chapters,
        ]);
    }
}
