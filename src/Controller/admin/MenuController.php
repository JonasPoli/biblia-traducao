<?php

namespace App\Controller\admin;

use App\Service\BibleDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function sidebar(BibleDataService $bibleDataService): Response
    {
        $books = $bibleDataService->getBooks();
        
        // Separate NT (40-66) and OT (1-39)
        $nt = array_filter($books, fn($book) => $book['id'] >= 40);
        $ot = array_filter($books, fn($book) => $book['id'] < 40);
        
        // Merge: NT first, then OT
        $reorderedBooks = array_merge($nt, $ot);

        return $this->render('admin/menu/sidebar.html.twig', [
            'books' => $reorderedBooks,
        ]);
    }
}
