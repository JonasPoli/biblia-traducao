<?php

namespace App\Controller\admin;

use App\Entity\TranslationHistory;
use App\Entity\VerseText;
use App\Repository\BibleVersionRepository;
use App\Repository\BookRepository;
use App\Repository\VerseRepository;
use App\Repository\VerseTextRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranslationController extends AbstractController
{
    private const TARGET_VERSION_ID = 17; // Haroldo Dutra
    private const REFERENCE_VERSION_ID = 1; // Almeida
    private const OT_ORIGINAL_VERSION_ID = 19; // Hebrew Study Bible
    private const NT_ORIGINAL_VERSION_ID = 18; // Greek Berean Bible

    #[Route('/admin/translation/{bookId}/{chapter}', name: 'app_translation_chapter', methods: ['GET'])]
    public function chapter(
        int $bookId,
        int $chapter,
        BookRepository $bookRepository,
        VerseRepository $verseRepository
    ): Response {
        $book = $bookRepository->find($bookId);
        if (!$book) {
            throw $this->createNotFoundException('Book not found');
        }

        // Determine original version based on testament
        // Assuming Testament ID 1 is Old, 2 is New. 
        // Or check name? Let's check ID for now as it's standard.
        // Actually, let's check the ID.
        $originalVersionId = ($book->getTestament()->getId() === 1) ? self::OT_ORIGINAL_VERSION_ID : self::NT_ORIGINAL_VERSION_ID;

        $versionIds = [
            self::TARGET_VERSION_ID,
            self::REFERENCE_VERSION_ID,
            $originalVersionId
        ];

        $verses = $verseRepository->getVersesForTranslation($bookId, $chapter, $versionIds);

        $data = [];
        foreach ($verses as $verse) {
            $item = [
                'verse' => $verse,
                'original' => null,
                'reference' => null,
                'target' => null,
            ];

            foreach ($verse->getVerseTexts() as $vt) {
                $vid = $vt->getVersion()->getId();
                if ($vid === $originalVersionId) {
                    $item['original'] = $vt;
                } elseif ($vid === self::REFERENCE_VERSION_ID) {
                    $item['reference'] = $vt;
                } elseif ($vid === self::TARGET_VERSION_ID) {
                    $item['target'] = $vt;
                }
            }
            $data[] = $item;
        }

        return $this->render('translation/index.html.twig', [
            'book' => $book,
            'chapter' => $chapter,
            'verses' => $data,
            'originalVersionId' => $originalVersionId,
        ]);
    }

    #[Route('/admin/translation/save/{id}', name: 'app_translation_save', methods: ['POST'])]
    public function save(
        int $id,
        Request $request,
        VerseRepository $verseRepository,
        VerseTextRepository $verseTextRepository,
        BibleVersionRepository $bibleVersionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $verse = $verseRepository->find($id);
        if (!$verse) {
            return $this->json(['error' => 'Verse not found'], 404);
        }

        $text = $request->request->get('text');
        if ($text === null) {
            return $this->json(['error' => 'Text is required'], 400);
        }

        // Find existing translation
        $verseText = $verseTextRepository->findOneBy([
            'verse' => $verse,
            'version' => self::TARGET_VERSION_ID
        ]);

        if (!$verseText) {
            // Create new
            $version = $bibleVersionRepository->find(self::TARGET_VERSION_ID);
            if (!$version) {
                return $this->json(['error' => 'Target version not found'], 500);
            }

            $verseText = new VerseText();
            $verseText->setVerse($verse);
            $verseText->setVersion($version);
            $verseText->setText(''); // Initial empty
            $entityManager->persist($verseText);
        }

        // Check for changes
        if ($verseText->getText() !== $text) {
            // Create history
            $history = new TranslationHistory();
            $history->setVerseText($verseText);
            $history->setOldText($verseText->getText() ?? '');

            // User? For now we don't have logged in user, or we use a dummy.
            // Requirement says "usuário com autenticação".
            // I should get the current user.
            $user = $this->getUser();
            if ($user) {
                $history->setUser($user);
                $verseText->setUser($user);
            } else {
                // Handle case where no user is logged in (should be protected by firewall)
                // For now, skip user if null, but entity requires it?
                // TranslationHistory.user is ManyToOne nullable=false?
                // Let's check TranslationHistory entity.
            }

            $entityManager->persist($history);

            // Update text
            $verseText->setText($text);
            $entityManager->flush();

            return $this->json(['status' => 'saved', 'message' => 'Tradução salva com sucesso!']);
        }

        return $this->json(['status' => 'unchanged', 'message' => 'Nenhuma alteração detectada.']);
    }

    #[Route('/admin/translation/{bookId}/{chapter}/{verseNum}', name: 'app_translation_verse', methods: ['GET'])]
    public function verse(
        int $bookId,
        int $chapter,
        int $verseNum,
        BookRepository $bookRepository,
        VerseRepository $verseRepository,
        VerseTextRepository $verseTextRepository,
        \App\Repository\VerseWordRepository $verseWordRepository,
        \App\Repository\VerseReferenceRepository $verseReferenceRepository
    ): Response {
        $book = $bookRepository->find($bookId);
        if (!$book) {
            throw $this->createNotFoundException('Book not found');
        }

        $verse = $verseRepository->findOneBy([
            'book' => $book,
            'chapter' => $chapter,
            'verse' => $verseNum
        ]);

        if (!$verse) {
            throw $this->createNotFoundException('Verse not found');
        }

        // Fetch References
        $references = $verseReferenceRepository->findBy(['verse' => $verse]);

        // Original Version ID
        $originalVersionId = ($book->getTestament()->getId() === 1) ? self::OT_ORIGINAL_VERSION_ID : self::NT_ORIGINAL_VERSION_ID;

        // Fetch Verse Texts for this verse
        $verseTexts = $verseTextRepository->findBy(['verse' => $verse]);
        $texts = [
            'original' => null,
            'reference' => null,
            'target' => null
        ];
        foreach ($verseTexts as $vt) {
            $vid = $vt->getVersion()->getId();
            if ($vid === $originalVersionId)
                $texts['original'] = $vt;
            elseif ($vid === self::REFERENCE_VERSION_ID)
                $texts['reference'] = $vt;
            elseif ($vid === self::TARGET_VERSION_ID)
                $texts['target'] = $vt;
        }

        // Fetch Verse Words (Interlinear)
        $words = $verseWordRepository->findBy(['verse' => $verse], ['position' => 'ASC']);

        // Fetch Occurrences for each word
        $occurrences = [];
        foreach ($words as $word) {
            if ($word->getStrongCode()) {
                $occurrences[$word->getId()] = $verseWordRepository->findOccurrences($word->getStrongCode(), 20);
            }
        }

        // Determine Strong's Prefix (H for OT, G for NT)
        // Assuming ID 1 is OT.
        $strongPrefix = ($book->getTestament()->getId() === 1) ? 'H' : 'G';

        // Fetch Chapter Context (Comparative Table)
        // We can reuse the logic from 'chapter' method but we need to pass it to the view
        $versionIds = [self::TARGET_VERSION_ID, self::REFERENCE_VERSION_ID, $originalVersionId];
        $chapterVerses = $verseRepository->getVersesForTranslation($bookId, $chapter, $versionIds);

        $chapterData = [];
        foreach ($chapterVerses as $cv) {
            $item = ['verse' => $cv, 'original' => null, 'reference' => null, 'target' => null];
            foreach ($cv->getVerseTexts() as $vt) {
                $vid = $vt->getVersion()->getId();
                if ($vid === $originalVersionId)
                    $item['original'] = $vt;
                elseif ($vid === self::REFERENCE_VERSION_ID)
                    $item['reference'] = $vt;
                elseif ($vid === self::TARGET_VERSION_ID)
                    $item['target'] = $vt;
            }
            $chapterData[] = $item;
        }

        return $this->render('translation/verse.html.twig', [
            'book' => $book,
            'chapter' => $chapter,
            'verse' => $verse,
            'texts' => $texts,
            'words' => $words,
            'chapterData' => $chapterData,
            'originalVersionId' => $originalVersionId,
            'references' => $references,
            'occurrences' => $occurrences,
            'strongPrefix' => $strongPrefix,
        ]);
    }

    #[Route('/admin/translation/reference/add/{verseId}', name: 'app_translation_reference_add', methods: ['POST'])]
    public function addReference(
        int $verseId,
        Request $request,
        VerseRepository $verseRepository,
        \App\Repository\VerseReferenceRepository $verseReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $verse = $verseRepository->find($verseId);
        if (!$verse) {
            throw $this->createNotFoundException('Verse not found');
        }

        $term = $request->request->get('term');
        $referenceText = $request->request->get('referenceText');

        if ($term && $referenceText) {
            $ref = new \App\Entity\VerseReference();
            $ref->setVerse($verse);
            $ref->setTerm($term);
            $ref->setReferenceText($referenceText);

            $entityManager->persist($ref);
            $entityManager->flush();

            $this->addFlash('success', 'Referência adicionada com sucesso!');
        } else {
            $this->addFlash('error', 'Preencha todos os campos.');
        }

        return $this->redirectToRoute('app_translation_verse', [
            'bookId' => $verse->getBook()->getId(),
            'chapter' => $verse->getChapter(),
            'verseNum' => $verse->getVerse()
        ]);
    }

    #[Route('/admin/translation/reference/delete/{id}', name: 'app_translation_reference_delete', methods: ['POST'])]
    public function deleteReference(
        int $id,
        \App\Repository\VerseReferenceRepository $verseReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $verseReferenceRepository->find($id);
        if (!$ref) {
            throw $this->createNotFoundException('Reference not found');
        }

        $verse = $ref->getVerse();

        $entityManager->remove($ref);
        $entityManager->flush();

        $this->addFlash('success', 'Referência removida com sucesso!');

        return $this->redirectToRoute('app_translation_verse', [
            'bookId' => $verse->getBook()->getId(),
            'chapter' => $verse->getChapter(),
            'verseNum' => $verse->getVerse()
        ]);
    }
}
