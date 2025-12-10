<?php

namespace App\Service;

class StrongFormatter
{
    private array $grammaticalMap = [
        // Nouns & Basic Types
        'n' => 'substantivo',
        'v' => 'verbo',
        'adj' => 'adjetivo',
        'adv' => 'advérbio',
        'prep' => 'preposição',
        'conj' => 'conjunção',
        'pron' => 'pronome',
        'part' => 'partícula',
        'interj' => 'interjeição',
        'indecl' => 'indeclinável',

        // Attributes
        'pr' => 'próprio', // proper
        'pers' => 'pessoal',
        'rel' => 'relativo',
        'recip' => 'recíproco',
        'dem' => 'demonstrativo',
        'interrog' => 'interrogativo',
        'indef' => 'indefinido',
        'refl' => 'reflexivo',

        // Gender
        'm' => 'masculino',
        'f' => 'feminino',
        'neut' => 'neutro',

        // Number
        'sg' => 'singular',
        'pl' => 'plural',

        // Case (Greek mostly)
        'gen' => 'genitivo',
        'dat' => 'dativo',
        'acc' => 'acusativo',
        'nom' => 'nominativo',
        'voc' => 'vocativo',

        // Voice/Tense/Mood
        // Voice/Tense/Mood
        'trans' => 'transitivo',
        'intrans' => 'intransitivo',
        'act' => 'ativo',
        'mid' => 'médio',
        'pass' => 'passivo',
        'pres' => 'presente',
        'fut' => 'futuro',
        'perf' => 'perfeito',
        'impf' => 'imperfeito',
        'aor' => 'aoristo',
        'indic' => 'indicativo',
        'impv' => 'imperativo',
        'subj' => 'subjuntivo',
        'opt' => 'optativo',
        'inf' => 'infinitivo',
        'ptcp' => 'particípio',

        // Extended
        'compar' => 'comparativo',
        'superl' => 'superlativo',
        'contr' => 'contração',
        'neg' => 'negativo',
        'cl' => 'clítico',
        'excl' => 'exclamação',
        'irr' => 'irregular',
        'dep' => 'depoente',
        'def' => 'definido',
        'second' => 'segundo',
        'aor-2' => 'aoristo segundo',
    ];

    /**
     * Converts technical Strong codes (e.g. "TDNT - 3:284,360; n pr m" or "DITAT - 167; n f")
     * into readable explanation.
     */
    public function transform(string $html): string
    {
        $html = $this->replaceGrammarCodes($html);

        // Clean HTML to fix UI issues (User report: "font too big, bold everywhere")
        // 1. Remove Headers (h1-h6) and replace with simple breaks or divs
        $html = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/si', '<div class="font-bold mt-2 mb-1">$1</div>', $html);

        // 2. Remove all <b> and <strong> tags to reset "bold everywhere"
        $html = preg_replace('/<(?:b|strong)[^>]*>(.*?)<\/(?:b|strong)>/si', '$1', $html);

        // 3. Remove <font> tags which might set sizes
        $html = preg_replace('/<font[^>]*>(.*?)<\/font>/si', '$1', $html);

        // Convert <p> tags to styled lists
        // User requested splitting text by ";" and capitalizing each part.
        if (str_contains($html, '<p')) {
            $html = preg_replace_callback(
                '/<p[^>]*>(.*?)<\/p>/s',
                function ($matches) {
                    $content = $matches[1];
                    // Split by semicolon
                    $parts = explode(';', $content);
                    $listItems = '';

                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (empty($part))
                            continue;

                        // Capitalize first letter (multibyte safe)
                        $firstChar = mb_substr($part, 0, 1);
                        $rest = mb_substr($part, 1);
                        $part = mb_strtoupper($firstChar) . $rest;

                        // Ensure ends with period
                        if (mb_substr($part, -1) !== '.') {
                            $part .= '.';
                        }

                        // Use inline styles for list items as previously established for compatibility
                        $listItems .= "<li style=\"margin-bottom: 4px;\">{$part}</li>";
                    }

                    if (empty($listItems))
                        return '';

                    return "<div class=\"text-gray-800\"><ul style=\"list-style-type: disc !important; padding-left: 20px !important; margin-top: 4px; margin-bottom: 8px;\">{$listItems}</ul></div>";
                },
                $html
            );
        }

        // Format nested lists (c1, c2, c3) often found in Strong's definitions
        // Using regex to be robust against spacing and quote types

        // c1 -> outline level 1 (1, 2, 3...)
        // Using inline styles to avoid Tailwind purging issues since this path might not be scanned
        $html = preg_replace(
            '/<ol\s+class=["\']c1["\']>/i',
            '<ol style="list-style-type: decimal !important; padding-left: 24px !important; margin-top: 4px; margin-bottom: 8px; color: #374151;">',
            $html
        );

        // c2 -> outline level 2 (a, b, c...)
        $html = preg_replace(
            '/<ol\s+class=["\']c2["\']>/i',
            '<ol style="list-style-type: lower-alpha !important; padding-left: 24px !important; margin-top: 4px; margin-bottom: 4px; color: #4b5563;">',
            $html
        );

        // c3 -> outline level 3 (i, ii, iii...)
        $html = preg_replace(
            '/<ol\s+class=["\']c3["\']>/i',
            '<ol style="list-style-type: lower-roman !important; padding-left: 24px !important; margin-top: 2px; margin-bottom: 4px; color: #6b7280; font-size: 0.875rem;">',
            $html
        );

        // Ensure standard li inside these ols render correctly (display: list-item)
        $html = str_replace('<li>', '<li style="display: list-item !important; margin-bottom: 4px;">', $html);

        return $html;
    }

    /**
     * Parses technical codes like "TDNT - ...", "DITAT - ...", or just "6; n. pr. m."
     */
    private function replaceGrammarCodes(string $text): string
    {
        /**
         * Regex to capture:
         *  Group 1: Dictionary Name (Optional) -> (TDNT|DITAT|TWOT)?
         *  Group 2: Separator and Reference -> \s*-\s* ... OR just at start?
         *
         * Let's try a more flexible approach to catch "6; n. pr. m." which lacks "DITAT - " prefix
         * Pattern 1: Explicit Dictionary: (TDNT|DITAT|TWOT)\s*-\s*([0-9:a-zA-Z,*]+);\s*([a-z0-9 .\-]+)
         * Pattern 2: Implicit/Short Ref:  ^(\d+);\s*([a-z0-9 .\-]+)  (at start of line or string)
         *
         * We can try to handle both or simplify.
         * Let's restart the regex logic to be:
         * ((?:TDNT|DITAT|TWOT)\s*-\s*)?([0-9:a-zA-Z,*]+);\s*([a-z0-9 .\-]+)
         */

        $pattern = '/(?:(TDNT|DITAT|TWOT)\s*-\s*)?([0-9:a-zA-Z,*]+);\s*([a-z0-9 .\-]+)/i';

        return preg_replace_callback($pattern, function ($matches) {
            // If group 1 is empty, it means no dictionary name was found
            // matches[1] might be empty string if captured but not matched, or offset 1 if strict.
            // With (?:(..)...)? structure:
            // If "TDNT - 1:2; n" -> 1="TDNT", 2="1:2", 3="n"
            // If "1:2; n"        -> 1="", 2="1:2", 3="n"

            $dict = !empty($matches[1]) ? strtoupper($matches[1]) : '';
            $refRaw = $matches[2];
            $codeRaw = strtolower(trim($matches[3]));

            // Expand Dictionary Name
            if ($dict) {
                $dictName = match ($dict) {
                    'TDNT' => 'Theological Dictionary of the New Testament (TDNT)',
                    'DITAT', 'TWOT' => 'Dicionário Internacional de Teologia do Antigo Testamento (DITAT/TWOT)',
                    default => $dict
                };
                $dictPrefix = "referência ao {$dictName}, ";
            } else {
                // If no dictionary, maybe imply one if we knew context, or just say 'referência'
                // User example "6; n. pr. m." -> likely just "referência 6..."
                $dictPrefix = "referência ";
            }

            // Format formatting logic: "volume X, páginas Y" vs just "X"
            $ref = str_replace(',', ', ', $refRaw);
            if (str_contains($ref, ':')) {
                $parts = explode(':', $ref);
                $vol = $parts[0];
                $pages = $parts[1] ?? '';
                $refText = "volume {$vol}, páginas {$pages}";
            } else {
                $refText = "{$ref}";
            }

            // Split code by space
            $parts = explode(' ', $codeRaw);
            $translatedParts = [];
            foreach ($parts as $p) {
                $cleanP = rtrim($p, '.');
                $val = $this->grammaticalMap[$cleanP] ?? null;

                // If not found, maybe it's punctuation or unknown?
                // Just keep original if not found
                $translatedParts[] = $val ?? $p;
            }
            $desc = implode(' ', $translatedParts);

            // "referência ao ..., volume ..., páginas ...; substantivo..."
            // "referência 6; substantivo..."
            return "{$dictPrefix}{$refText}; {$desc}";
        }, $text);
    }




    public function formatFullDefinition(?string $fullDef): string
    {
        if (!$fullDef)
            return '';

        $formattedFullDef = '';
        $lines = explode("\n", $fullDef);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            $indentClass = '';
            if (preg_match('/^\d+[a-z]\d+[a-z]\)/', $line))
                $indentClass = 'pl-12';
            elseif (preg_match('/^\d+[a-z]\d+\)/', $line))
                $indentClass = 'pl-8';
            elseif (preg_match('/^\d+[a-z]\)/', $line))
                $indentClass = 'pl-4';
            elseif (preg_match('/^\d+\)/', $line))
                $indentClass = '';

            // Expand grammar codes within the line
            $line = $this->replaceGrammarCodes($line);

            $formattedFullDef .= "<div class=\"{$indentClass}\">{$line}</div>";
        }

        return $formattedFullDef;
    }
}
