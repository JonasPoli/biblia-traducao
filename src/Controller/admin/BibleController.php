<?php

namespace App\Controller\admin;

use App\Repository\BookRepository;
use App\Repository\VerseRepository;
use App\Repository\VerseTextRepository;
use App\Repository\GlobalReferenceRepository;
use App\Repository\VerseReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

final class BibleController extends AbstractController
{
    private const TARGET_VERSION_ID = 17; // Haroldo Dutra

    #[Route('/admin/bible/print/{bookId}', name: 'app_bible_print_book', methods: ['GET'])]
    public function printBook(
        int $bookId,
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

        // Fetch all verses for the book
        $verses = $verseRepository->findBy(['book' => $book], ['chapter' => 'ASC', 'verse' => 'ASC']);
        
        // Fetch all texts for the target version
        $verseTexts = $verseTextRepository->findByVersionAndBook(self::TARGET_VERSION_ID, $bookId);
        $textsByVerseId = [];
        foreach ($verseTexts as $vt) {
            $textsByVerseId[$vt->getVerse()->getId()] = $vt;
        }

        // Fetch References
        $globalReferences = $globalReferenceRepository->findAll();
        $specificReferences = $verseReferenceRepository->findByBook($bookId);

        // Organize data by chapter
        $chapters = [];
        $refCounter = 1;
        $currentChapter = 0;

        foreach ($verses as $verse) {
            if ($verse->getChapter() !== $currentChapter) {
                $currentChapter = $verse->getChapter();
                $refCounter = 1; // Reset counter per chapter
                $chapters[$currentChapter] = [
                    'verses' => [],
                    'references' => []
                ];
            }

            $vt = $textsByVerseId[$verse->getId()] ?? null;
            $text = $vt ? $vt->getText() : '';
            // Strip P tags from text
            $text = strip_tags($text, '<strong><em><b><i><u><span>'); // Keep basic formatting but remove block tags like <p>
            
            $title = $vt ? $vt->getTitle() : null;

            // Process References for this verse
            $verseRefs = [];
            
            // 1. Global References
            foreach ($globalReferences as $gr) {
                if ($gr->getTerm() && stripos($text, $gr->getTerm()) !== false) {
                    $verseRefs[] = [
                        'type' => 'global',
                        'term' => $gr->getTerm(),
                        'text' => strip_tags($gr->getReferenceText(), '<strong><em><b><i><u><span>'), // Strip P tags
                        'obj' => $gr
                    ];
                }
            }

            // 2. Specific References
            foreach ($specificReferences as $sr) {
                if ($sr->getVerse()->getId() === $verse->getId()) {
                    $verseRefs[] = [
                        'type' => 'specific',
                        'term' => $sr->getTerm(),
                        'text' => strip_tags($sr->getReferenceText(), '<strong><em><b><i><u><span>'), // Strip P tags
                        'obj' => $sr
                    ];
                }
            }

            // Sort references by position
            $refsWithPos = [];
            $alreadyUsedPositions = []; // Track positions we've already used
            
            foreach ($verseRefs as $ref) {
                $term = $ref['term'];
                $pos = 0;
                
                if ($term) {
                    // Find first occurrence (case-insensitive)
                    $foundPos = stripos($text, $term);
                    if ($foundPos !== false) {
                        $pos = $foundPos;
                        
                        // If this exact position was already used by another ref, skip this one
                        $posKey = $pos . '_' . strtolower($term);
                        if (isset($alreadyUsedPositions[$posKey])) {
                            continue; // Skip duplicate
                        }
                        $alreadyUsedPositions[$posKey] = true;
                    } else {
                        // Term not found, default to 0 (beginning)
                        $pos = 0;
                    }
                }
                
                $refsWithPos[] = [
                    'pos' => $pos,
                    'ref' => $ref
                ];
            }

            // Sort by position ASC to assign IDs in order of appearance
            usort($refsWithPos, function ($a, $b) {
                if ($a['pos'] === $b['pos']) return 0;
                return ($a['pos'] < $b['pos']) ? -1 : 1;
            });

            // Assign IDs
            $finalRefs = [];
            foreach ($refsWithPos as $item) {
                $refId = $refCounter++;
                $finalRefs[] = [
                    'id' => $refId,
                    'pos' => $item['pos'],
                    'ref' => $item['ref']
                ];
            }

            // Sort by position DESC for safe injection (injecting later in string doesn't affect earlier offsets)
            usort($finalRefs, function ($a, $b) {
                if ($a['pos'] === $b['pos']) {
                    // If same position, inject higher ID first so it appears last? 
                    // No, if we inject at pos 0: "<sup>1</sup>" then "<sup>2</sup>".
                    // If we inject 2 then 1 at same pos: "<sup>2</sup>..." then "<sup>1</sup><sup>2</sup>...".
                    // So for same pos, we want higher ID first if we are prepending to the same point?
                    // Let's stick to stable sort logic or just handle collision.
                    return 0; 
                }
                return ($a['pos'] > $b['pos']) ? -1 : 1;
            });

            $processedText = $text;
            $verseReferences = [];

            foreach ($finalRefs as $item) {
                $refId = $item['id'];
                $pos = $item['pos'];
                $ref = $item['ref'];
                
                $marker = "<sup class='ref-marker'>$refId</sup>";
                
                // Inject at position
                // We use substr_replace to insert at exact position
                // Note: $processedText grows, but since we sort DESC, the $pos is valid for the original string parts before insertion points.
                // Wait, $pos is based on ORIGINAL string.
                // If we inject at 100, then at 10, the insertion at 100 doesn't affect index 10.
                // So DESC sort is correct.
                
                $processedText = substr_replace($processedText, $marker, $pos, 0);

                // Add to list for footer (sorted by ID)
                $verseReferences[$refId] = [ // Use ID as key to sort later easily
                    'id' => $refId,
                    'verseNum' => $verse->getVerse(),
                    'text' => $ref['text']
                ];
            }
            
            // Sort footer references by ID
            ksort($verseReferences);
            $verseReferences = array_values($verseReferences);

            $chapters[$currentChapter]['verses'][] = [
                'num' => $verse->getVerse(),
                'text' => $processedText,
                'title' => $title
            ];
            
            // Merge references
            $chapters[$currentChapter]['references'] = array_merge($chapters[$currentChapter]['references'], $verseReferences);
        }

        return $this->render('admin/bible/print_book.html.twig', [
            'book' => $book,
            'chapters' => $chapters,
        ]);
    }

    #[Route('/admin/bible/latex/{bookId}', name: 'app_bible_download_latex', methods: ['GET'])]
    public function downloadLatex(
        int $bookId,
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

        // Fetch Data (Similar to printBook)
        $verses = $verseRepository->findBy(['book' => $book], ['chapter' => 'ASC', 'verse' => 'ASC']);
        $verseTexts = $verseTextRepository->findByVersionAndBook(self::TARGET_VERSION_ID, $bookId);
        $textsByVerseId = [];
        foreach ($verseTexts as $vt) {
            $textsByVerseId[$vt->getVerse()->getId()] = $vt;
        }
        $globalReferences = $globalReferenceRepository->findAll();
        $specificReferences = $verseReferenceRepository->findByBook($bookId);

        // LaTeX Header
        $latex = <<<'EOT'
% !TeX program = xelatex
\documentclass[8pt,twocolumn]{article}

% Increased bottom margin to prevent overflow
\usepackage[a5paper,left=1cm,right=1cm,top=1cm,bottom=2cm]{geometry}
\setlength{\columnsep}{0.6cm}
\setlength{\columnseprule}{0.3pt}

% Removed multicol to ensure footnotes stay in their column
% \usepackage{multicol} 

\usepackage{iftex}
\ifPDFTeX
  \usepackage[T1]{fontenc}
  \usepackage[utf8]{inputenc}
  \usepackage{newtxtext}
  \PackageWarning{biblia}{Please use XeLaTeX or LuaLaTeX for proper Unicode support!}
\else
  \usepackage{fontspec}
  % Linux Libertine O has excellent support for Greek and Hebrew
  \setmainfont{Linux Libertine O}
\fi

\usepackage[brazil]{babel}
\usepackage{microtype}
\usepackage{lettrine}

% Configure footnote spacing and limits
\interfootnotelinepenalty=10000
\setlength{\skip\footins}{1em}
\setlength{\footnotesep}{0.7em}
\dimen\footins=0.6\textheight

\pretolerance=1000
\tolerance=2000
\emergencystretch=2em
\setlength{\parindent}{0.8em}
\setlength{\parskip}{0pt}
\setlength{\baselineskip}{10pt}

\newcounter{chapter}
\newcounter{verse}
% xref counter removed, using native footnote counter

\newcommand{\Book}[1]{%
  \twocolumn[%
    \centering
    {\Huge\bfseries #1}
    \bigskip
    \bigskip
  ]%
  \setcounter{chapter}{0}%
  \setcounter{verse}{0}%
  \setcounter{footnote}{0}% Reset footnote counter per book
}

\newcommand{\Chapter}{%
  \stepcounter{chapter}%
  \setcounter{verse}{0}%
  \setcounter{footnote}{0}% Reset footnote counter per chapter
}

\newcommand{\Assunto}[1]{%
  \par\medskip
  {\centering\itshape\small #1\par}
  \medskip
}

\newcommand{\printversenum}{%
  \ifnum\value{verse}>1
    \raisebox{0.4ex}{\textbf{\small\theverse}}~%
  \fi
}

\newcommand{\Verse}[1]{%
  \par
  \stepcounter{verse}%
  \noindent
  \ifnum\value{verse}=1
    \lettrine[lines=2, findent=3pt, nindent=0pt]{\thechapter}{}%
    #1%
  \else
    \printversenum
    #1%
  \fi
}

% Customize footnote marker (Reference number in text)
% Sobrescrito, fonte ligeiramente menor, sem negrito e com italico
\makeatletter
\renewcommand{\@makefnmark}{\hbox{\textsuperscript{\textit{\@thefnmark}}}}

% Use native LaTeX footnote with 60% font size relative to current (body) size
\newcommand{\xref}[1]{%
  \footnote{\fontsize{0.7\dimexpr\f@size pt\relax}{0.84\dimexpr\f@size pt\relax}\selectfont #1}%
}
\makeatother

\begin{document}

EOT;

        $latex .= sprintf("\\Book{%s}\n\n", $this->escapeLatex($book->getName()));

        $currentChapter = 0;
        $refCounter = 1;
        $chapterGenerated = false; // Track if we've output \Chapter for current chapter

        foreach ($verses as $verse) {
            // Check if we're starting a new chapter
            if ($verse->getChapter() !== $currentChapter) {
                $currentChapter = $verse->getChapter();
                $refCounter = 1; // Reset reference counter for new chapter
                $chapterGenerated = false; // Mark that we haven't generated \Chapter yet
            }

            $vt = $textsByVerseId[$verse->getId()] ?? null;
            $text = $vt ? $vt->getText() : '';
            // Decode HTML entities FIRST, then strip tags
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = strip_tags($text);
            
            // Skip verses with no text (DISABLED: User reported missing verses)
            // if (trim($text) === '') {
            //    continue;
            // }
            
            // Generate \Chapter only when we have the first verse with text
            if (!$chapterGenerated) {
                $latex .= "\\Chapter\n";
                $chapterGenerated = true;
            }
            
            $title = $vt ? $vt->getTitle() : null;

            if ($title) {
                $decodedTitle = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $latex .= sprintf("\\Assunto{%s}\n", $this->escapeLatex($decodedTitle));
            }

            // Process References - VERY AGGRESSIVE deduplication
            $verseRefs = [];
            $seenTermsExact = []; // Track exact normalized terms to prevent ANY duplicates
            
            // 1. First, collect Specific References (they have priority)
            foreach ($specificReferences as $sr) {
                if ($sr->getVerse()->getId() !== $verse->getId()) {
                    continue; // Not for this verse
                }
                
                $term = trim($sr->getTerm() ?: '');
                if (!$term) continue; // Skip empty terms
                
                // Normalize for comparison
                $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                
                // STRICT: Skip if we've already seen this exact term
                if (isset($seenTermsExact[$termNormalized])) {
                    continue; // Already have a reference for this term
                }
                
                $refText = html_entity_decode($sr->getReferenceText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $refText = strip_tags($refText);
                
                $verseRefs[] = [
                    'term' => $term,
                    'text' => $refText,
                ];
                
                $seenTermsExact[$termNormalized] = true;
            }
            
            // 2. Then, collect Global References (only if term not already used)
            foreach ($globalReferences as $gr) {
                $term = trim($gr->getTerm() ?: '');
                if (!$term) continue;
                
                // Normalize for comparison
                $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                
                // STRICT: Skip if this term was already used
                if (isset($seenTermsExact[$termNormalized])) {
                    continue;
                }
                
                // Only include if term actually appears in the text
                if (stripos($text, $term) !== false) {
                    $refText = html_entity_decode($gr->getReferenceText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $refText = strip_tags($refText);
                    
                    $verseRefs[] = [
                        'term' => $term,
                        'text' => $refText,
                    ];
                    
                    $seenTermsExact[$termNormalized] = true;
                }
            }
            
            // Final safeguard: remove any duplicates by term
            $uniqueByTerm = [];
            $dedupedRefs = [];
            foreach ($verseRefs as $ref) {
                $termKey = strtolower($ref['term']);
                if (!isset($uniqueByTerm[$termKey])) {
                    $dedupedRefs[] = $ref;
                    $uniqueByTerm[$termKey] = true;
                }
            }
            $verseRefs = $dedupedRefs;

            // Sort references by position
            $refsWithPos = [];
            foreach ($verseRefs as $ref) {
                $term = $ref['term'];
                $pos = 0;
                if ($term) {
                    $foundPos = stripos($text, $term);
                    $pos = ($foundPos !== false) ? $foundPos : 0;
                }
                $refsWithPos[] = ['pos' => $pos, 'ref' => $ref];
            }

            usort($refsWithPos, function ($a, $b) {
                if ($a['pos'] === $b['pos']) return 0;
                return ($a['pos'] < $b['pos']) ? -1 : 1;
            });

            // Assign IDs in order of appearance
            $finalRefs = [];
            foreach ($refsWithPos as $item) {
                $refId = $refCounter++;
                $finalRefs[] = [
                    'id' => $refId,
                    'pos' => $item['pos'],
                    'ref' => $item['ref']
                ];
            }

            // Keep ASC order for injection (so numbers appear 1, 2, 3... in reading order)
            // We'll track offset as we inject
            $processedText = $text;
            $offset = 0; // Track how much the string has grown
            $placeholders = [];

            foreach ($finalRefs as $item) {
                $refId = $item['id'];
                $pos = $item['pos'];
                $ref = $item['ref'];
                
                $placeholder = "[[REF_{$refId}]]";
                $refContent = $this->escapeLatex($ref['text']);
                
                // Enforce integer casting to ensure clean numbers (e.g. "4" instead of "04")
                $verseRefText = sprintf("%d:%d", (int)$verse->getChapter(), (int)$verse->getVerse());
                
                // Pass reference text with bold chapter:verse prefix
                $latexCommand = sprintf("\\xref{\\textbf{%s} %s}", $verseRefText, $refContent);
                $placeholders[$placeholder] = $latexCommand;

                // Inject placeholder at adjusted position
                $adjustedPos = $pos + $offset;
                $processedText = substr_replace($processedText, $placeholder, $adjustedPos, 0);
                
                // Update offset for next injection
                $offset += strlen($placeholder);
            }

            // Escape the text for LaTeX
            $finalText = $this->escapeLatex($processedText);

            // Restore placeholders with actual commands
            foreach ($placeholders as $ph => $cmd) {
                $finalText = str_replace($this->escapeLatex($ph), $cmd, $finalText);
            }

            $latex .= sprintf("\\Verse{%s}\n\n", $finalText);
        }

        // Close multicols environment opened in \Book (REMOVED: using standard twocolumn)
        // $latex .= "\n\\end{multicols}\n";
        $latex .= "\\end{document}";

        return new Response($latex, 200, [
            'Content-Type' => 'application/x-latex',
            'Content-Disposition' => 'attachment; filename="' . $book->getName() . '.tex"',
        ]);
    }

    #[Route('/admin/bible/epub/{bookId}', name: 'app_bible_download_epub', methods: ['GET'])]
    public function downloadEpub(
        int $bookId,
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

        $verses = $verseRepository->findBy(['book' => $book], ['chapter' => 'ASC', 'verse' => 'ASC']);
        $verseTexts = $verseTextRepository->findByVersionAndBook(self::TARGET_VERSION_ID, $bookId);
        $textsByVerseId = [];
        foreach ($verseTexts as $vt) {
            $textsByVerseId[$vt->getVerse()->getId()] = $vt;
        }

        $globalReferences = $globalReferenceRepository->findAll();
        $specificReferences = $verseReferenceRepository->findByBook($bookId);

        $tempFile = tempnam(sys_get_temp_dir(), 'epub');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

        $containerXml = '<?xml version="1.0" encoding="UTF-8" ?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>';
        $zip->addFromString('META-INF/container.xml', $containerXml);

        $css = 'body { font-family: serif; line-height: 1.5; margin: 5%; }
h1 { text-align: center; margin-bottom: 2em; }
h2 { text-align: center; margin-top: 1em; margin-bottom: 1em; display: none; } /* Hide chapter number header as it is in drop cap */
.chapter-number { font-size: 3em; float: left; margin-right: 0.1em; line-height: 0.8; font-weight: bold; }
.verse-number { font-size: 0.75em; vertical-align: super; font-weight: bold; margin-right: 0.2em; }
.subject { text-align: center; font-style: italic; margin: 1em 0; font-size: 0.9em; display: block; width: 100%; }
.verse { margin-bottom: 0.5em; text-align: justify; }
a.footnote-link { vertical-align: super; font-size: 0.75em; text-decoration: none; color: inherit; }
.footnotes { margin-top: 2em; border-top: 1px solid #ccc; padding-top: 1em; }
.footnote-item { font-size: 0.9em; margin-bottom: 0.5em; }
.footnote-back { text-decoration: none; color: inherit; }';
        $zip->addFromString('OEBPS/styles.css', $css);

        // Organize data
        $chapters = [];
        foreach ($verses as $verse) {
            $chapters[$verse->getChapter()][] = $verse;
        }

        $manifestItems = '<item id="styles" href="styles.css" media-type="text/css"/>' . "\n";
        $spineItems = '';
        $navPoints = '';
        
        // Generate Chapters
        foreach ($chapters as $chapterNum => $chapterVerses) {
            $chapterId = "chapter_{$chapterNum}";
            $filename = "{$chapterId}.xhtml";
            
            $manifestItems .= '<item id="' . $chapterId . '" href="' . $filename . '" media-type="application/xhtml+xml"/>' . "\n";
            $spineItems .= '<itemref idref="' . $chapterId . '"/>' . "\n";
            $navPoints .= '<navPoint id="navPoint-' . $chapterNum . '" playOrder="' . $chapterNum . '">
                <navLabel><text>Chapter ' . $chapterNum . '</text></navLabel>
                <content src="' . $filename . '"/>
            </navPoint>' . "\n";

            $content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
    <title>' . $book->getName() . ' ' . $chapterNum . '</title>
    <link rel="stylesheet" type="text/css" href="styles.css" />
</head>
<body>';
            
            // Only show Book Title on first chapter
            if ($chapterNum == 1) {
                $content .= '<h1>' . htmlspecialchars($book->getName()) . '</h1>';
            }

            $footnotesHtml = '';
            $refCounter = 1;
            $chapterGenerated = false;

            foreach ($chapterVerses as $index => $verse) {
                $vt = $textsByVerseId[$verse->getId()] ?? null;
                $text = $vt ? $vt->getText() : '';
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = strip_tags($text);

                // Handle Subject
                $title = $vt ? $vt->getTitle() : null;
                if ($title) {
                    $decodedTitle = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $content .= '<div class="subject">' . htmlspecialchars($decodedTitle) . '</div>';
                }

                // Process References (Reuse logic from downloadLatex but adapted for HTML)
                $verseRefs = [];
                $seenTermsExact = [];
                
                // 1. Specific References
                foreach ($specificReferences as $sr) {
                    if ($sr->getVerse()->getId() !== $verse->getId()) continue;
                    $term = trim($sr->getTerm() ?: '');
                    if (!$term) continue;
                    $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                    if (isset($seenTermsExact[$termNormalized])) continue;
                    
                    $refText = html_entity_decode($sr->getReferenceText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $refText = strip_tags($refText);
                    $verseRefs[] = ['term' => $term, 'text' => $refText];
                    $seenTermsExact[$termNormalized] = true;
                }

                // 2. Global References
                foreach ($globalReferences as $gr) {
                    $term = trim($gr->getTerm() ?: '');
                    if (!$term) continue;
                    $termNormalized = preg_replace('/\s+/', ' ', strtolower($term));
                    if (isset($seenTermsExact[$termNormalized])) continue;
                    
                    if (stripos($text, $term) !== false) {
                        $refText = html_entity_decode($gr->getReferenceText(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $refText = strip_tags($refText);
                        $verseRefs[] = ['term' => $term, 'text' => $refText];
                        $seenTermsExact[$termNormalized] = true;
                    }
                }

                // Sort and Dedup (simplified)
                $refsWithPos = [];
                foreach ($verseRefs as $ref) {
                    $term = $ref['term'];
                    $pos = 0;
                    if ($term) {
                        $foundPos = stripos($text, $term);
                        $pos = ($foundPos !== false) ? $foundPos : 0;
                    }
                    $refsWithPos[] = ['pos' => $pos, 'ref' => $ref];
                }
                usort($refsWithPos, fn($a, $b) => $a['pos'] <=> $b['pos']);

                // Inject References
                $processedText = $text;
                $offset = 0;
                $placeholders = [];
                
                foreach ($refsWithPos as $item) {
                    $refId = $refCounter++;
                    $pos = $item['pos'];
                    $ref = $item['ref'];
                    
                    $placeholder = "[[REF_{$refId}]]";
                    $noteId = "note-{$chapterNum}-{$refId}";
                    $refLinkId = "ref-{$chapterNum}-{$refId}";
                    
                    // HTML Link
                    $linkHtml = sprintf(
                        '<a href="#%s" id="%s" class="footnote-link" epub:type="noteref">[%d]</a>',
                        $noteId,
                        $refLinkId,
                        $refId
                    );
                    
                    $placeholders[$placeholder] = $linkHtml;
                    
                    // Add to footnotes
                    $verseRefText = sprintf("%d:%d", $verse->getChapter(), $verse->getVerse());
                    $footnotesHtml .= sprintf(
                        '<div class="footnote-item" id="%s"><a href="#%s" class="footnote-back">^</a> <strong>%s</strong> %s</div>',
                        $noteId,
                        $refLinkId,
                        $verseRefText,
                        htmlspecialchars($ref['text'])
                    );

                    $adjustedPos = $pos + $offset;
                    $processedText = substr_replace($processedText, $placeholder, $adjustedPos, 0);
                    $offset += strlen($placeholder);
                }

                $finalText = htmlspecialchars($processedText);
                foreach ($placeholders as $ph => $html) {
                    $finalText = str_replace(htmlspecialchars($ph), $html, $finalText);
                }

                // Verse Output
                $content .= '<p class="verse">';
                if ($verse->getVerse() == 1) {
                    $content .= '<span class="chapter-number">' . $chapterNum . '</span>';
                } else {
                    $content .= '<span class="verse-number">' . $verse->getVerse() . '</span>';
                }
                $content .= $finalText . '</p>';
            }

            if (!empty($footnotesHtml)) {
                $content .= '<div class="footnotes" epub:type="footnotes">' . $footnotesHtml . '</div>';
            }

            $content .= '</body></html>';
            $zip->addFromString('OEBPS/' . $filename, $content);
        }

        // Content.opf
        $opf = '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="BookId" version="3.0">
    <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
        <dc:title>' . htmlspecialchars($book->getName()) . '</dc:title>
        <dc:language>pt-BR</dc:language>
        <dc:identifier id="BookId">urn:uuid:' . uniqid() . '</dc:identifier>
    </metadata>
    <manifest>
        <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
        ' . $manifestItems . '
    </manifest>
    <spine toc="ncx">
        ' . $spineItems . '
    </spine>
</package>';
        $zip->addFromString('OEBPS/content.opf', $opf);

        // TOC.ncx
        $ncx = '<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
    <head>
        <meta name="dtb:uid" content="urn:uuid:12345"/>
        <meta name="dtb:depth" content="1"/>
        <meta name="dtb:totalPageCount" content="0"/>
        <meta name="dtb:maxPageNumber" content="0"/>
    </head>
    <docTitle><text>' . htmlspecialchars($book->getName()) . '</text></docTitle>
    <navMap>
        ' . $navPoints . '
    </navMap>
</ncx>';
        $zip->addFromString('OEBPS/toc.ncx', $ncx);

        $zip->close();

        $response = new Response(file_get_contents($tempFile));
        $response->headers->set('Content-Type', 'application/epub+zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $book->getName() . '.epub"');
        
        unlink($tempFile);
        
        return $response;
    }

    private function escapeLatex(string $text): string
    {
        $map = [
            '\\' => '\\textbackslash{}',
            '{'  => '\\{',
            '}'  => '\\}',
            '$'  => '\\$',
            '&'  => '\\&',
            '#'  => '\\#',
            '^'  => '\\textasciicircum{}',
            '_'  => '\\_',
            '~'  => '\\textasciitilde{}',
            '%'  => '\\%',
        ];
        return str_replace(array_keys($map), array_values($map), $text);
    }
}
