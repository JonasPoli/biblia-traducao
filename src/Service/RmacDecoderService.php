<?php

namespace App\Service;

class RmacDecoderService
{
    private const TABLE_A_CLASS = [
        '1' => 'Adjetivo',
        '2' => 'Advérbio',
        '3' => 'Conjunção',
        '4' => 'Interjeição',
        '5' => 'Substantivo',
        '6' => 'Preposição',
        '7' => 'Artigo',
        '8' => 'Verbo',
    ];

    private const TABLE_B_FLEX = [
        // Key: digit
        // Value: [Verbo => Tempo, Subst/Adj => Caso]
        '1' => ['Verbo' => 'Aoristo', 'Subst' => 'Nominativo'],
        '2' => ['Verbo' => 'Perfeito', 'Subst' => 'Genitivo'],
        '3' => ['Verbo' => 'Perfeito Futuro', 'Subst' => 'Dativo'],
        '4' => ['Verbo' => 'Futuro', 'Subst' => 'Acusativo'],
        '5' => ['Verbo' => 'Presente', 'Subst' => 'Vocativo'],
        '6' => ['Verbo' => 'Mais-que-Perfeito', 'Subst' => null],
    ];

    private const TABLE_C_FLEX = [
        // Key: digit
        // Value: [Verbo => Modo, Subst/Adj => Gênero]
        '1' => ['Verbo' => 'Indicativo', 'Subst' => 'Masculino'],
        '2' => ['Verbo' => 'Infinitivo', 'Subst' => 'Feminino'],
        '3' => ['Verbo' => 'Particípio', 'Subst' => 'Neutro'],
        '4' => ['Verbo' => 'Subjuntivo', 'Subst' => null],
        '6' => ['Verbo' => 'Optativo', 'Subst' => null],
        '7' => ['Verbo' => 'Imperativo', 'Subst' => null],
    ];

    private const TABLE_D_FLEX = [
        // Key: digit
        // Value: [Verbo => Voz, Subst/Adj => Número]
        '1' => ['Verbo' => 'Média', 'Subst' => 'Singular'],
        '2' => ['Verbo' => 'Ativa', 'Subst' => 'Plural'],
        '3' => ['Verbo' => 'Passiva', 'Subst' => null],
    ];

    private const TABLE_E_FLEX = [
        // Key: digit
        // Value: [Verbo => Pessoa, Adj => Grau]
        '1' => ['Verbo' => '1ª Pessoa', 'Adj' => 'Positivo'],
        '2' => ['Verbo' => '2ª Pessoa', 'Adj' => 'Comparativo'],
        '3' => ['Verbo' => '3ª Pessoa', 'Adj' => 'Superlativo'],
        '0' => ['Verbo' => '2ª Pessoa Plural', 'Adj' => null],
    ];

    private const TABLE_F_GENDER = [
        '1' => 'Masculino',
        '2' => 'Feminino',
        '3' => 'Neutro',
    ];

    private const TABLE_G_NUMBER = [
        '1' => 'Singular',
        '2' => 'Plural',
    ];

    public function decode(string $rmac): string
    {
        // 1. Validation
        if (!str_starts_with($rmac, 'G')) {
            return $rmac; // Return original if not a Greek RMAC
        }

        $digits = substr($rmac, 1);
        $len = strlen($digits);
        if ($len === 0) {
            return '';
        }

        $parts = [];

        // 2. Determine Word Class (2nd Position)
        // If length < 7 and digits suggest verb features, infer '8' (Verb)
        // Or if the first digit is 5 (Present), 1 (Aorist), etc. and it's short code.
        // Actually, standard RMAC:
        // Noun: N- (e.g. N-NSM) - wait, this is Robinson's numeric code?
        // The user provided numeric tables. "G5720".
        // 5 = Present (3rd pos), 7 = Imperative (4th pos), 2 = Active (5th pos), 0 = 2nd Plural (6th pos).
        // Where is the 2nd pos? "G5720" has 4 digits.
        // If it was "G85720", 8 = Verb.
        // User says: "If length < 7 ... infer 2nd position is 8 (Verb)".
        
        $classDigit = '';
        $remainingDigits = $digits;

        // Heuristic from user instructions:
        // "Extract numeric part (e.g. 5720). If length < 7 ... infer 2nd position is 8"
        // But wait, if it's "G2288" (Noun), 2=Genitive, 2=Feminine? No.
        // Let's look at G2288 (θάνατος - death). Usually Noun.
        // If G2288 is a Strong Number, that's different from RMAC.
        // The user example: "sabei<S>G1097</S> ... <S>G5720</S>"
        // G1097 is Strong. G5720 is RMAC.
        // G5720 -> 5 (Present), 7 (Imperative), 2 (Active), 0 (2nd Pl).
        // This matches the Verb tables (B, C, D, E) perfectly if we assume it's a Verb.
        
        // How to distinguish G2288 (Strong) from G2288 (RMAC)?
        // In the text parsing, RMAC is in a separate <S> tag, usually starting with G but following the Strong ID.
        // But here we just get the string "G5720".
        
        // User logic: "If length < 7 and subsequent digits indicate Tense/Mood/Voice... infer Verb".
        // Let's assume if it doesn't start with 1-7, it's a Verb (8) omitted.
        // Or rather, if the first digit matches a Tense (1-6) and the second matches a Mood (1-7)...
        
        // Let's try to parse the first digit as Class.
        $firstDigit = $digits[0];
        
        if (isset(self::TABLE_A_CLASS[$firstDigit])) {
            // It starts with a class digit (1-7, or 8)
            // But wait, 5 is Noun. 5 is also Present (Tense).
            // If "G5720" is passed, is 5 a Noun or Present?
            // If 5 is Noun, then 7 (2nd digit) would be Case? 7 is not in Table B (Case goes 1-5).
            // So 5 cannot be Noun here because 7 is invalid Case.
            // Thus 5 must be Tense, implying Class is Verb (8).
            
            // Logic: Check if first digit is a valid Class.
            // If yes, check if second digit is valid for that Class.
            // If not valid, assume Class 8 (Verb) and first digit is Tense.
            
            $isExplicitClass = true;
            $potentialClass = $firstDigit;
            
            // Check validity of next digit
            if ($len > 1) {
                $nextDigit = $digits[1];
                if ($potentialClass === '5') { // Noun
                    // Check if next digit is valid Case (1-5)
                    if (!isset(self::TABLE_B_FLEX[$nextDigit]['Subst'])) {
                        $isExplicitClass = false;
                    }
                }
                // Add more checks if needed, but the Noun/Verb ambiguity is the main one.
            }
            
            if ($isExplicitClass && $potentialClass !== '8') {
                 $classDigit = $potentialClass;
                 $remainingDigits = substr($digits, 1);
            } else {
                $classDigit = '8'; // Verb
                // Don't consume the digit, it's part of the flex
            }
        } else {
            // First digit not in 1-8? Unlikely for valid RMAC.
            // Assume Verb if not found?
            $classDigit = '8';
        }

        $className = self::TABLE_A_CLASS[$classDigit] ?? 'Desconhecido';
        $parts[] = $className;

        $isVerb = ($classDigit === '8');
        $isNounOrAdj = in_array($classDigit, ['1', '5', '7']); // Adj, Noun, Article
        $isAdj = ($classDigit === '1');

        // 3. Decode Sequential
        // Pad remaining digits to avoid errors?
        // "G5720" -> digits "5720".
        // Pos 3 (Tense/Case) -> '5'
        // Pos 4 (Mood/Gender) -> '7'
        // Pos 5 (Voice/Num) -> '2'
        // Pos 6 (Person/Deg) -> '0'
        
        $idx = 0;
        
        // 3rd Position: Tense or Case
        if ($idx < strlen($remainingDigits)) {
            $d = $remainingDigits[$idx++];
            if ($isVerb) {
                if (isset(self::TABLE_B_FLEX[$d]['Verbo'])) $parts[] = self::TABLE_B_FLEX[$d]['Verbo'];
            } elseif ($isNounOrAdj) {
                if (isset(self::TABLE_B_FLEX[$d]['Subst'])) $parts[] = self::TABLE_B_FLEX[$d]['Subst'];
            }
        }

        // 4th Position: Mood or Gender
        if ($idx < strlen($remainingDigits)) {
            $d = $remainingDigits[$idx++];
            if ($isVerb) {
                if (isset(self::TABLE_C_FLEX[$d]['Verbo'])) $parts[] = self::TABLE_C_FLEX[$d]['Verbo'];
            } elseif ($isNounOrAdj || $classDigit === '7') { // Article too
                if (isset(self::TABLE_C_FLEX[$d]['Subst'])) $parts[] = self::TABLE_C_FLEX[$d]['Subst'];
            }
        }

        // 5th Position: Voice or Number
        if ($idx < strlen($remainingDigits)) {
            $d = $remainingDigits[$idx++];
            if ($isVerb) {
                if (isset(self::TABLE_D_FLEX[$d]['Verbo'])) $parts[] = self::TABLE_D_FLEX[$d]['Verbo'];
            } elseif ($isNounOrAdj) {
                if (isset(self::TABLE_D_FLEX[$d]['Subst'])) $parts[] = self::TABLE_D_FLEX[$d]['Subst'];
            }
        }

        // 6th Position: Person or Degree
        if ($idx < strlen($remainingDigits)) {
            $d = $remainingDigits[$idx++];
            if ($isVerb) {
                if (isset(self::TABLE_E_FLEX[$d]['Verbo'])) $parts[] = self::TABLE_E_FLEX[$d]['Verbo'];
            } elseif ($isAdj) {
                if (isset(self::TABLE_E_FLEX[$d]['Adj'])) $parts[] = self::TABLE_E_FLEX[$d]['Adj'];
            }
        }

        // 7th & 8th: Gender & Number for Participles/Infinitives
        // Only if Mood was Participle (3) or Infinitive (2)? 
        // User says: "Pos 6 onwards... only if appropriate (e.g. 7th pos only if 4th pos is 3 (Participle))"
        // Let's check if we have a Participle.
        // 4th pos digit was at index 1 of remainingDigits.
        $moodDigit = $remainingDigits[1] ?? null;
        
        if ($isVerb && ($moodDigit === '3' || $moodDigit === '2')) {
             // 7th Position: Gender
             if ($idx < strlen($remainingDigits)) {
                 $d = $remainingDigits[$idx++];
                 if (isset(self::TABLE_F_GENDER[$d])) $parts[] = self::TABLE_F_GENDER[$d];
             }
             // 8th Position: Number
             if ($idx < strlen($remainingDigits)) {
                 $d = $remainingDigits[$idx++];
                 if (isset(self::TABLE_G_NUMBER[$d])) $parts[] = self::TABLE_G_NUMBER[$d];
             }
        }

        return implode(', ', $parts);
    }
}
