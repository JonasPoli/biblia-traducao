<?php

namespace App\Controller\admin;

use App\Entity\TranslationHistory;
use App\Entity\VerseText;
use App\Repository\BibleVersionRepository;
use App\Repository\BookRepository;
use App\Repository\VerseRepository;
use App\Repository\VerseTextRepository;
use App\Repository\GlobalReferenceRepository;
use App\Repository\VerseReferenceRepository;
use App\Repository\StrongDefinitionRepository;
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
        VerseRepository $verseRepository,
        VerseTextRepository $verseTextRepository,
        GlobalReferenceRepository $globalReferenceRepository,
        VerseReferenceRepository $verseReferenceRepository
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
        // Fetch verses for the chapter, including Version 22 (Almeida + Strongs)
        $versionIds = [
            self::TARGET_VERSION_ID,
            self::REFERENCE_VERSION_ID,
            $originalVersionId,
            22 // Almeida + Strongs
        ];

        $verses = $verseRepository->findBy(['book' => $book, 'chapter' => $chapter], ['verse' => 'ASC']);

        // Fetch References
        $globalReferences = $globalReferenceRepository->findAll();
        $specificReferences = $verseReferenceRepository->findByBook($bookId);

        $data = [];
        $footnotes = [];
        $refCounter = 1;

        foreach ($verses as $verse) {
            $item = [];
            $item['verse'] = $verse;

            // Fetch texts
            $textOriginal = $verseTextRepository->findOneBy(['verse' => $verse, 'version' => 5]); // Greek/Hebrew
            $text22 = $verseTextRepository->findOneBy(['verse' => $verse, 'version' => 22]); // Almeida
            $textTarget = $verseTextRepository->findOneBy(['verse' => $verse, 'version' => 17]); // Target (Haroldo)
            $textReference = $verseTextRepository->findOneBy(['verse' => $verse, 'version' => 18]); // Reference (KJA)

            $item['original'] = $textOriginal;
            $item['reference'] = $textReference;
            $item['target'] = $textTarget;

            // Process Target Text for References
            $processedText = $textTarget ? $textTarget->getText() : '';
            $processedText = strip_tags($processedText, '<strong><em><b><i><u><span>'); // Keep basic formatting

            // Logic to inject references (similar to BibleController)
            $verseRefs = [];
            $seenTermsExact = [];

            // 1. Specific References
            foreach ($specificReferences as $sr) {
                if ($sr->getVerse()->getId() !== $verse->getId())
                    continue;
                $term = trim($sr->getTerm() ?: '');
                if (!$term)
                    continue;
                $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                if (isset($seenTermsExact[$termNormalized]))
                    continue;

                $verseRefs[] = ['term' => $term, 'text' => $sr->getReferenceText(), 'obj' => $sr];
                $seenTermsExact[$termNormalized] = true;
            }

            // 2. Global References
            foreach ($globalReferences as $gr) {
                $term = trim($gr->getTerm() ?: '');
                if (!$term)
                    continue;
                $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                if (isset($seenTermsExact[$termNormalized]))
                    continue;

                if (stripos($processedText, $term) !== false) {
                    $verseRefs[] = ['term' => $term, 'text' => $gr->getReferenceText(), 'obj' => $gr];
                    $seenTermsExact[$termNormalized] = true;
                }
            }

            // Sort and Dedup
            $refsWithPos = [];
            foreach ($verseRefs as $ref) {
                $term = $ref['term'];
                $pos = 0;
                if ($term) {
                    $foundPos = stripos($processedText, $term);
                    $pos = ($foundPos !== false) ? $foundPos : 0;
                }
                $refsWithPos[] = ['pos' => $pos, 'ref' => $ref];
            }
            usort($refsWithPos, fn($a, $b) => $a['pos'] <=> $b['pos']);

            // Inject References
            $offset = 0;

            foreach ($refsWithPos as $itemRef) {
                $refId = $refCounter++;
                $pos = $itemRef['pos'];
                $ref = $itemRef['ref'];

                // Marker for HTML
                $marker = "<sup class=\"text-[10px] italic text-gray-500 cursor-pointer hover:underline px-1 rounded\" onclick=\"document.getElementById('footnote-{$refId}').scrollIntoView({behavior: 'smooth'})\">{$refId}</sup>";

                $adjustedPos = $pos + $offset;
                $processedText = substr_replace($processedText, $marker, $adjustedPos, 0);
                $offset += strlen($marker);

                // Add to footnotes
                $footnotes[] = [
                    'id' => $refId,
                    'verse' => $verse->getVerse(),
                    'text' => $ref['text'],
                    'term' => $ref['term']
                ];
            }

            $item['processed_text'] = $processedText;

            if ($text22) {
                $text22Content = $text22->getText();
                $referenceHtml = '';
                // Refined regex to exclude '>' from translation to avoid capturing tags like <pb/> partially
                preg_match_all('/(?P<translation>[^<>]+)<S>(?P<strongCode>[HG]\d+)<\/S>\s*<n>(?P<original>[^<]+)<\/n>/u', $text22Content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $strongCode = $match['strongCode'];
                    $translationWord = trim($match['translation']);

                    // Clean translation word
                    $translationWordClean = preg_replace('/[.,!?:;()"\'-]+/', ' ', $translationWord);
                    // Remove artifacts like pb/, /S, etc.
                    $translationWordClean = str_replace(['/S>', '<S>', '</S>', 'pb/>', 'pb/'], '', $translationWordClean);
                    $translationWordClean = trim($translationWordClean);

                    // Use cleaned translation word
                    $referenceHtml .= "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$translationWordClean}</span> ";
                }
                $item['almeida_html'] = trim($referenceHtml);
            }

            // Generate Original HTML from VerseWords
            $originalHtml = '';
            foreach ($verse->getVerseWords() as $word) {
                $strongCode = $word->getStrongCode();
                $originalWord = $word->getWordOriginal();

                if ($strongCode) {
                    $ptType = $word->getPortugueseType();
                    $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$originalWord}</span>";

                    if ($ptType) {
                        $originalHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                    } else {
                        $originalHtml .= "{$span} ";
                    }
                } else {
                    $originalHtml .= "{$originalWord} ";
                }
            }
            $item['original_html'] = trim($originalHtml);

            // Generate English HTML from VerseWords
            $englishHtml = '';
            foreach ($verse->getVerseWords() as $word) {
                $strongCode = $word->getStrongCode();
                $englishWord = $word->getWordEnglish();

                if ($englishWord) {
                    // Replace spaces with non-breaking spaces to keep phrases together
                    $englishWord = str_replace(' ', '&nbsp;', $englishWord);

                    if ($strongCode) {
                        $ptType = $word->getPortugueseType();
                        $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$englishWord}</span>";

                        if ($ptType) {
                            $englishHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                        } else {
                            $englishHtml .= "{$span} ";
                        }
                    } else {
                        $englishHtml .= "{$englishWord} ";
                    }
                }
            }
            $item['english_html'] = trim($englishHtml);

            $data[] = $item;
        }

        return $this->render('translation/index.html.twig', [
            'book' => $book,
            'chapter' => $chapter,
            'verses' => $data,
            'footnotes' => $footnotes,
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
        $title = $request->request->get('title');

        if (!$text) {
            // Allow saving empty text? Maybe. But let's assume at least something or just handle it.
            // If empty, it might mean clearing the translation.
        }
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
        $textChanged = $verseText->getText() !== $text;
        $titleChanged = $verseText->getTitle() !== $title;

        if ($textChanged || $titleChanged) {
            // Create history
            $history = new TranslationHistory();
            $history->setVerseText($verseText);
            $history->setOldText($verseText->getText() ?? '');

            // Note: We are not tracking title history separately for now, 
            // but the history entry is created if either changes.

            $user = $this->getUser();
            if ($user) {
                $history->setUser($user);
                $verseText->setUser($user);
            }

            $entityManager->persist($history);

            // Update text and title
            $verseText->setText($text);
            $verseText->setTitle($title);
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
        \App\Repository\VerseReferenceRepository $verseReferenceRepository,
        \App\Repository\GlobalReferenceRepository $globalReferenceRepository,

        StrongDefinitionRepository $strongDefinitionRepository,
        \App\Service\StrongFormatter $strongFormatter
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
            if ($word->getStrongDefinition()) {
                $occurrences[$word->getId()] = $verseWordRepository->findOccurrences($word->getStrongDefinition(), 20);
            }
        }

        // Determine Strong's Prefix (H for OT, G for NT)
        // Assuming ID 1 is OT.
        $strongPrefix = ($book->getTestament()->getId() === 1) ? 'H' : 'G';

        // Fetch Chapter Context (Comparative Table)
        // We can reuse the logic from 'chapter' method but we need to pass it to the view
        $versionIds = [self::TARGET_VERSION_ID, self::REFERENCE_VERSION_ID, $originalVersionId, 22];
        $chapterVerses = $verseRepository->getVersesForTranslation($bookId, $chapter, $versionIds);

        $chapterData = [];
        foreach ($chapterVerses as $cv) {
            $item = ['verse' => $cv, 'original' => null, 'reference' => null, 'target' => null];
            $text22 = null;
            foreach ($cv->getVerseTexts() as $vt) {
                $vid = $vt->getVersion()->getId();
                if ($vid === $originalVersionId)
                    $item['original'] = $vt;
                elseif ($vid === self::REFERENCE_VERSION_ID)
                    $item['reference'] = $vt;
                elseif ($vid === self::TARGET_VERSION_ID)
                    $item['target'] = $vt;
                elseif ($vid === 22)
                    $text22 = $vt->getText();
            }

            if ($text22) {
                $originalHtml = '';
                $referenceHtml = '';

                // Refined regex to exclude '>' from translation to avoid capturing tags like <pb/> partially
                preg_match_all('/(?P<translation>[^<>]+)<S>(?P<strongCode>[HG]\d+)<\/S>\s*<n>(?P<original>[^<]+)<\/n>/u', $text22, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $strongCode = $match['strongCode'];
                    $translationWord = trim($match['translation']);

                    // Clean translation word
                    $translationWordClean = preg_replace('/[.,!?:;()"\'-]+/', ' ', $translationWord);
                    // Remove artifacts like pb/, /S, etc.
                    $translationWordClean = str_replace(['/S>', '<S>', '</S>', 'pb/>', 'pb/'], '', $translationWordClean);
                    $translationWordClean = trim($translationWordClean);

                    // Use cleaned translation word
                    $referenceHtml .= "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$translationWordClean}</span> ";
                }
                $item['reference_html'] = trim($referenceHtml);
            }

            // Generate Original HTML from VerseWords
            $originalHtml = '';
            foreach ($cv->getVerseWords() as $word) {
                $strongCode = $word->getStrongCode();
                $originalWord = $word->getWordOriginal();

                if ($strongCode) {
                    $ptType = $word->getPortugueseType();
                    $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\" data-original=\"{$originalWord}\">{$originalWord}</span>";

                    if ($ptType) {
                        $originalHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                    } else {
                        $originalHtml .= "{$span} ";
                    }
                } else {
                    $originalHtml .= "{$originalWord} ";
                }
            }
            $item['original_html'] = trim($originalHtml);

            // Generate English HTML from VerseWords
            $englishHtml = '';
            foreach ($cv->getVerseWords() as $word) {
                $strongCode = $word->getStrongCode();
                $englishWord = $word->getWordEnglish();

                if ($englishWord) {
                    // Replace spaces with non-breaking spaces to keep phrases together
                    $englishWord = str_replace(' ', '&nbsp;', $englishWord);

                    if ($strongCode) {
                        $ptType = $word->getPortugueseType();
                        $originalWordEscaped = htmlspecialchars($word->getWordOriginal(), ENT_QUOTES);
                        $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\" data-original=\"{$originalWordEscaped}\">{$englishWord}</span>";

                        if ($ptType) {
                            $englishHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                        } else {
                            $englishHtml .= "{$span} ";
                        }
                    } else {
                        $englishHtml .= "{$englishWord} ";
                    }
                }
            }
            $item['english_html'] = trim($englishHtml);

            $chapterData[] = $item;
        }

        // Collect all unique Strong codes from the chapter to fetch definitions for the footer
        $strongCodes = [];
        foreach ($chapterData as $row) {
            if (isset($row['original_html'])) {
                preg_match_all('/data-strong="([^"]+)"/', $row['original_html'], $matches);
                if (!empty($matches[1])) {
                    $strongCodes = array_merge($strongCodes, $matches[1]);
                }
            }
        }
        $strongCodes = array_unique($strongCodes);

        // Fetch definitions
        $strongDefinitions = [];
        if (!empty($strongCodes)) {
            $definitions = $strongDefinitionRepository->findBy(['code' => $strongCodes]);
            foreach ($definitions as $def) {
                // Format Full Definition Hierarchy
                $formattedFullDef = $strongFormatter->formatFullDefinition($def->getFullDefinition());

                $strongDefinitions[$def->getCode()] = [
                    'title' => $def->getHebrewWord() ?: $def->getGreekWord(),
                    'originalWord' => $def->getHebrewWord() ?: $def->getGreekWord(),
                    'code' => $def->getCode(),
                    'transliteration' => $def->getTransliteration(),
                    'fullDefinition' => $formattedFullDef, // Pre-formatted HTML
                    'definition' => $strongFormatter->transform($def->getDefinition() ?? ''),
                    'pronunciation' => $def->getPronunciation(),
                    'lemma' => $def->getLemma()
                ];
            }
        }

        // Fetch Global References
        $allGlobalReferences = $globalReferenceRepository->findAll();
        $globalReferences = [];

        // Filter based on Target Translation (Haroldo Dutra)
        // Check if the GlobalReference 'term' appears in the translation text (case-insensitive)
        if ($texts['target']) {
            $targetText = $texts['target']->getText();
            foreach ($allGlobalReferences as $gr) {
                if ($gr->getTerm() && stripos($targetText, $gr->getTerm()) !== false) {
                    $globalReferences[] = $gr;
                }
            }
        }

        // Parse Version 22 (Almeida + Strongs) for Tabs AND Display
        $parsedWords = [];
        $almeidaHtml = null;
        $verseText22 = $verseTextRepository->findOneBy([
            'verse' => $verse,
            'version' => 22 // Almeida 21 + Strongs
        ]);

        if ($verseText22) {
            $text22 = $verseText22->getText();

            // Generate HTML for display (same logic as chapter view)
            $referenceHtml = '';
            preg_match_all('/(?P<translation>[^<>]+)<S>(?P<strongCode>[HG]\d+)<\/S>\s*<n>(?P<original>[^<]+)<\/n>/u', $text22, $matchesHtml, PREG_SET_ORDER);

            foreach ($matchesHtml as $match) {
                $strongCode = $match['strongCode'];
                $translationWord = trim($match['translation']);

                // Clean translation word
                $translationWordClean = preg_replace('/[.,!?:;()"\'-]+/', ' ', $translationWord);
                $translationWordClean = str_replace(['/S>', '<S>', '</S>', 'pb/>', 'pb/'], '', $translationWordClean);
                $translationWordClean = trim($translationWordClean);

                $referenceHtml .= "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$translationWordClean}</span> ";
            }
            $almeidaHtml = trim($referenceHtml);


            // Create a map of Strong Code -> Portuguese Type from VerseWords
            $strongTypeMap = [];
            foreach ($verse->getVerseWords() as $vw) {
                if ($vw->getStrongCode()) {
                    $strongTypeMap[$vw->getStrongCode()] = $vw->getPortugueseType();
                }
            }

            // Parse for Tabs (existing logic)
            // Regex to match: Translation<S>StrongCode</S> <n>Original</n>
            // Note: The text might have extra tags or spaces.
            // Example: Os homens<S>H582</S> <n>אֱנוֹשׁ</n><S>H582</S>
            // We capture: Translation (before <S>), StrongCode (inside first <S>), Original (inside <n>)

            // Refined regex to exclude '>' from translation to avoid capturing tags like <pb/> partially
            preg_match_all('/(?P<translation>[^<>]+)<S>(?P<strongCode>[HG]\d+)<\/S>\s*<n>(?P<original>[^<]+)<\/n>/u', $text22, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $strongCode = $match['strongCode'];
                $definition = $strongDefinitionRepository->findOneBy(['code' => $strongCode]);

                // Clean translation term: remove punctuation and trim
                $translation = trim($match['translation']);
                // Remove common punctuation and extra characters
                $translation = preg_replace('/[.,!?:;()"\'-]+/', ' ', $translation);
                // Also remove the specific case mentioned "/S>" if it somehow leaks, though regex should handle it.
                // And remove pb/> artifacts
                $translation = str_replace(['/S>', '<S>', '</S>', 'pb/>', 'pb/'], '', $translation);
                $translation = trim($translation);

                $parsedWords[] = [
                    'wordOriginal' => trim($match['original']),
                    'translation' => $translation,
                    'strongCode' => $strongCode,
                    'portugueseType' => $strongTypeMap[$strongCode] ?? '',
                    'transliteration' => $definition ? $definition->getTransliteration() : '',
                    'fullDefinition' => $definition ? $definition->getFullDefinition() : '',
                    'definition' => $definition ? $definition->getDefinition() : '',
                    'strongDefinition' => $definition
                ];
            }
        }

        // Generate Original HTML from VerseWords
        $originalHtml = '';
        foreach ($words as $word) {
            $strongCode = $word->getStrongCode();
            $originalWord = $word->getWordOriginal();

            if ($strongCode) {
                $ptType = $word->getPortugueseType();
                $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\" data-original=\"{$originalWord}\">{$originalWord}</span>";

                if ($ptType) {
                    $originalHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                } else {
                    $originalHtml .= "{$span} ";
                }
            } else {
                $originalHtml .= "{$originalWord} ";
            }
        }
        $originalHtml = trim($originalHtml);

        // Generate English HTML from VerseWords
        $englishHtml = '';
        foreach ($words as $word) {
            $strongCode = $word->getStrongCode();
            $englishWord = $word->getWordEnglish();

            if ($englishWord) {
                // Replace spaces with non-breaking spaces to keep phrases together
                $englishWord = str_replace(' ', '&nbsp;', $englishWord);

                if ($strongCode) {
                    $ptType = $word->getPortugueseType();
                    $originalWordEscaped = htmlspecialchars($word->getWordOriginal(), ENT_QUOTES);
                    $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\" data-original=\"{$originalWordEscaped}\">{$englishWord}</span>";

                    if ($ptType) {
                        $englishHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                    } else {
                        $englishHtml .= "{$span} ";
                    }
                } else {
                    $englishHtml .= "{$englishWord} ";
                }
            }
        }
        $englishHtml = trim($englishHtml);

        return $this->render('translation/verse.html.twig', [
            'book' => $book,
            'chapter' => $chapter,
            'verse' => $verse,
            'texts' => $texts,
            'words' => $words, // Keeping for backward compat or reference if needed, but tabs will use parsedWords
            'parsedWords' => $parsedWords,
            'almeidaHtml' => $almeidaHtml,
            'originalHtml' => $originalHtml,
            'englishHtml' => $englishHtml,
            'chapterData' => $chapterData,
            'originalVersionId' => $originalVersionId,
            'references' => $references,
            'globalReferences' => $globalReferences,
            'occurrences' => $occurrences,
            'strongPrefix' => $strongPrefix,
            'strongDefinitions' => $strongDefinitions,
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

    #[Route('/admin/translation/reference/edit/{id}', name: 'app_translation_reference_edit', methods: ['POST'])]
    public function editReference(
        int $id,
        Request $request,
        \App\Repository\VerseReferenceRepository $verseReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $verseReferenceRepository->find($id);
        if (!$ref) {
            throw $this->createNotFoundException('Reference not found');
        }

        $term = $request->request->get('term');
        $referenceText = $request->request->get('referenceText');

        if ($term && $referenceText) {
            $ref->setTerm($term);
            $ref->setReferenceText($referenceText);

            $entityManager->flush();

            $this->addFlash('success', 'Referência atualizada com sucesso!');
        } else {
            $this->addFlash('error', 'Preencha todos os campos.');
        }

        $verse = $ref->getVerse();
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

    #[Route('/admin/translation/global-reference/add/{verseId}', name: 'app_translation_global_reference_add', methods: ['POST'])]
    public function addGlobalReference(
        int $verseId,
        Request $request,
        VerseRepository $verseRepository,
        \App\Repository\GlobalReferenceRepository $globalReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $verse = $verseRepository->find($verseId);
        if (!$verse) {
            throw $this->createNotFoundException('Verse not found');
        }

        $term = $request->request->get('term');
        $referenceText = $request->request->get('referenceText');
        $foreignWord = $request->request->get('foreignWord');
        $strongId = $request->request->get('strongId');

        if ($term && $referenceText) {
            $ref = new \App\Entity\GlobalReference();
            $ref->setTerm($term);
            $ref->setReferenceText($referenceText);
            $ref->setForeignWord($foreignWord);
            $ref->setStrongId($strongId);

            $entityManager->persist($ref);
            $entityManager->flush();

            $this->addFlash('success', 'Referência global adicionada com sucesso!');
        } else {
            $this->addFlash('error', 'Preencha todos os campos obrigatórios.');
        }

        return $this->redirectToRoute('app_translation_verse', [
            'bookId' => $verse->getBook()->getId(),
            'chapter' => $verse->getChapter(),
            'verseNum' => $verse->getVerse()
        ]);
    }

    #[Route('/admin/translation/global-reference/edit/{id}', name: 'app_translation_global_reference_edit', methods: ['POST'])]
    public function editGlobalReference(
        int $id,
        Request $request,
        \App\Repository\GlobalReferenceRepository $globalReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $globalReferenceRepository->find($id);
        if (!$ref) {
            throw $this->createNotFoundException('Global Reference not found');
        }

        $term = $request->request->get('term');
        $referenceText = $request->request->get('referenceText');
        $foreignWord = $request->request->get('foreignWord');

        // We need verse info to redirect back. Since GlobalReference is not linked to verse,
        // we must get it from the request (referer or hidden field). 
        // Let's assume passed as query param or form field for redirection purposes.
        $bookId = $request->request->get('redirect_book_id');
        $chapter = $request->request->get('redirect_chapter');
        $verseNum = $request->request->get('redirect_verse_num');

        if ($term && $referenceText) {
            $ref->setTerm($term);
            $ref->setReferenceText($referenceText);
            $ref->setForeignWord($foreignWord);

            $entityManager->flush();

            $this->addFlash('success', 'Referência global atualizada com sucesso!');
        } else {
            $this->addFlash('error', 'Preencha todos os campos obrigatórios.');
        }

        return $this->redirectToRoute('app_translation_verse', [
            'bookId' => $bookId,
            'chapter' => $chapter,
            'verseNum' => $verseNum
        ]);
    }

    #[Route('/admin/translation/global-reference/delete/{id}', name: 'app_translation_global_reference_delete', methods: ['POST'])]
    public function deleteGlobalReference(
        int $id,
        Request $request,
        \App\Repository\GlobalReferenceRepository $globalReferenceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $ref = $globalReferenceRepository->find($id);
        if (!$ref) {
            throw $this->createNotFoundException('Global Reference not found');
        }

        $bookId = $request->request->get('redirect_book_id');
        $chapter = $request->request->get('redirect_chapter');
        $verseNum = $request->request->get('redirect_verse_num');

        $entityManager->remove($ref);
        $entityManager->flush();

        $this->addFlash('success', 'Referência global removida com sucesso!');

        return $this->redirectToRoute('app_translation_verse', [
            'bookId' => $bookId,
            'chapter' => $chapter,
            'verseNum' => $verseNum
        ]);
    }
}

