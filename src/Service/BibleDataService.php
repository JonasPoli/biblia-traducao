<?php

namespace App\Service;

class BibleDataService
{
    /**
     * Returns a structured array of all Bible books.
     * Useful for menu generation and reference parsing.
     *
     * @return array<int, array{id: int, name: string, abbreviation: string, testament: string, chapters: int}>
     */
    public function getBooks(): array
    {
        return [
            1 => ['id' => 1, 'name' => 'Gênesis', 'abbreviation' => 'gn', 'testament' => 'VT', 'chapters' => 50],
            2 => ['id' => 2, 'name' => 'Êxodo', 'abbreviation' => 'ex', 'testament' => 'VT', 'chapters' => 40],
            3 => ['id' => 3, 'name' => 'Levítico', 'abbreviation' => 'lv', 'testament' => 'VT', 'chapters' => 27],
            4 => ['id' => 4, 'name' => 'Números', 'abbreviation' => 'nm', 'testament' => 'VT', 'chapters' => 36],
            5 => ['id' => 5, 'name' => 'Deuteronômio', 'abbreviation' => 'dt', 'testament' => 'VT', 'chapters' => 34],
            6 => ['id' => 6, 'name' => 'Josué', 'abbreviation' => 'js', 'testament' => 'VT', 'chapters' => 24],
            7 => ['id' => 7, 'name' => 'Juízes', 'abbreviation' => 'jz', 'testament' => 'VT', 'chapters' => 21],
            8 => ['id' => 8, 'name' => 'Rute', 'abbreviation' => 'rt', 'testament' => 'VT', 'chapters' => 4],
            9 => ['id' => 9, 'name' => 'I Samuel', 'abbreviation' => '1sm', 'testament' => 'VT', 'chapters' => 31],
            10 => ['id' => 10, 'name' => 'II Samuel', 'abbreviation' => '2sm', 'testament' => 'VT', 'chapters' => 24],
            11 => ['id' => 11, 'name' => 'I Reis', 'abbreviation' => '1rs', 'testament' => 'VT', 'chapters' => 22],
            12 => ['id' => 12, 'name' => 'II Reis', 'abbreviation' => '2rs', 'testament' => 'VT', 'chapters' => 25],
            13 => ['id' => 13, 'name' => 'I Crônicas', 'abbreviation' => '1cr', 'testament' => 'VT', 'chapters' => 29],
            14 => ['id' => 14, 'name' => 'II Crônicas', 'abbreviation' => '2cr', 'testament' => 'VT', 'chapters' => 36],
            15 => ['id' => 15, 'name' => 'Esdras', 'abbreviation' => 'ed', 'testament' => 'VT', 'chapters' => 10],
            16 => ['id' => 16, 'name' => 'Neemias', 'abbreviation' => 'ne', 'testament' => 'VT', 'chapters' => 13],
            17 => ['id' => 17, 'name' => 'Ester', 'abbreviation' => 'et', 'testament' => 'VT', 'chapters' => 10],
            18 => ['id' => 18, 'name' => 'Jó', 'abbreviation' => 'jó', 'testament' => 'VT', 'chapters' => 42],
            19 => ['id' => 19, 'name' => 'Salmos', 'abbreviation' => 'sl', 'testament' => 'VT', 'chapters' => 150],
            20 => ['id' => 20, 'name' => 'Provérbios', 'abbreviation' => 'pv', 'testament' => 'VT', 'chapters' => 31],
            21 => ['id' => 21, 'name' => 'Eclesiastes', 'abbreviation' => 'ec', 'testament' => 'VT', 'chapters' => 12],
            22 => ['id' => 22, 'name' => 'Cântico dos Cânticos', 'abbreviation' => 'ct', 'testament' => 'VT', 'chapters' => 8],
            23 => ['id' => 23, 'name' => 'Isaías', 'abbreviation' => 'is', 'testament' => 'VT', 'chapters' => 66],
            24 => ['id' => 24, 'name' => 'Jeremias', 'abbreviation' => 'jr', 'testament' => 'VT', 'chapters' => 52],
            25 => ['id' => 25, 'name' => 'Lamentações de Jeremias', 'abbreviation' => 'lm', 'testament' => 'VT', 'chapters' => 5],
            26 => ['id' => 26, 'name' => 'Ezequiel', 'abbreviation' => 'ez', 'testament' => 'VT', 'chapters' => 48],
            27 => ['id' => 27, 'name' => 'Daniel', 'abbreviation' => 'dn', 'testament' => 'VT', 'chapters' => 12],
            28 => ['id' => 28, 'name' => 'Oséias', 'abbreviation' => 'os', 'testament' => 'VT', 'chapters' => 14],
            29 => ['id' => 29, 'name' => 'Joel', 'abbreviation' => 'jl', 'testament' => 'VT', 'chapters' => 3],
            30 => ['id' => 30, 'name' => 'Amós', 'abbreviation' => 'am', 'testament' => 'VT', 'chapters' => 9],
            31 => ['id' => 31, 'name' => 'Obadias', 'abbreviation' => 'ob', 'testament' => 'VT', 'chapters' => 1],
            32 => ['id' => 32, 'name' => 'Jonas', 'abbreviation' => 'jn', 'testament' => 'VT', 'chapters' => 4],
            33 => ['id' => 33, 'name' => 'Miquéias', 'abbreviation' => 'mq', 'testament' => 'VT', 'chapters' => 7],
            34 => ['id' => 34, 'name' => 'Naum', 'abbreviation' => 'na', 'testament' => 'VT', 'chapters' => 3],
            35 => ['id' => 35, 'name' => 'Habacuque', 'abbreviation' => 'hc', 'testament' => 'VT', 'chapters' => 3],
            36 => ['id' => 36, 'name' => 'Sofonias', 'abbreviation' => 'sf', 'testament' => 'VT', 'chapters' => 3],
            37 => ['id' => 37, 'name' => 'Ageu', 'abbreviation' => 'ag', 'testament' => 'VT', 'chapters' => 2],
            38 => ['id' => 38, 'name' => 'Zacarias', 'abbreviation' => 'zc', 'testament' => 'VT', 'chapters' => 14],
            39 => ['id' => 39, 'name' => 'Malaquias', 'abbreviation' => 'ml', 'testament' => 'VT', 'chapters' => 4],
            40 => ['id' => 40, 'name' => 'Mateus', 'abbreviation' => 'mt', 'testament' => 'NT', 'chapters' => 28],
            41 => ['id' => 41, 'name' => 'Marcos', 'abbreviation' => 'mc', 'testament' => 'NT', 'chapters' => 16],
            42 => ['id' => 42, 'name' => 'Lucas', 'abbreviation' => 'lc', 'testament' => 'NT', 'chapters' => 24],
            43 => ['id' => 43, 'name' => 'João', 'abbreviation' => 'jo', 'testament' => 'NT', 'chapters' => 21],
            44 => ['id' => 44, 'name' => 'Atos', 'abbreviation' => 'at', 'testament' => 'NT', 'chapters' => 28],
            45 => ['id' => 45, 'name' => 'Romanos', 'abbreviation' => 'rm', 'testament' => 'NT', 'chapters' => 16],
            46 => ['id' => 46, 'name' => 'I Coríntios', 'abbreviation' => '1co', 'testament' => 'NT', 'chapters' => 16],
            47 => ['id' => 47, 'name' => 'II Coríntios', 'abbreviation' => '2co', 'testament' => 'NT', 'chapters' => 13],
            48 => ['id' => 48, 'name' => 'Gálatas', 'abbreviation' => 'gl', 'testament' => 'NT', 'chapters' => 6],
            49 => ['id' => 49, 'name' => 'Efésios', 'abbreviation' => 'ef', 'testament' => 'NT', 'chapters' => 6],
            50 => ['id' => 50, 'name' => 'Filipenses', 'abbreviation' => 'fp', 'testament' => 'NT', 'chapters' => 4],
            51 => ['id' => 51, 'name' => 'Colossenses', 'abbreviation' => 'cl', 'testament' => 'NT', 'chapters' => 4],
            52 => ['id' => 52, 'name' => 'I Tessalonicenses', 'abbreviation' => '1ts', 'testament' => 'NT', 'chapters' => 5],
            53 => ['id' => 53, 'name' => 'II Tessalonicenses', 'abbreviation' => '2ts', 'testament' => 'NT', 'chapters' => 3],
            54 => ['id' => 54, 'name' => 'I Timóteo', 'abbreviation' => '1tm', 'testament' => 'NT', 'chapters' => 6],
            55 => ['id' => 55, 'name' => 'II Timóteo', 'abbreviation' => '2tm', 'testament' => 'NT', 'chapters' => 4],
            56 => ['id' => 56, 'name' => 'Tito', 'abbreviation' => 'tt', 'testament' => 'NT', 'chapters' => 3],
            57 => ['id' => 57, 'name' => 'Filemom', 'abbreviation' => 'fm', 'testament' => 'NT', 'chapters' => 1],
            58 => ['id' => 58, 'name' => 'Hebreus', 'abbreviation' => 'hb', 'testament' => 'NT', 'chapters' => 13],
            59 => ['id' => 59, 'name' => 'Tiago', 'abbreviation' => 'tg', 'testament' => 'NT', 'chapters' => 5],
            60 => ['id' => 60, 'name' => 'I Pedro', 'abbreviation' => '1pe', 'testament' => 'NT', 'chapters' => 5],
            61 => ['id' => 61, 'name' => 'II Pedro', 'abbreviation' => '2pe', 'testament' => 'NT', 'chapters' => 3],
            62 => ['id' => 62, 'name' => 'I João', 'abbreviation' => '1jo', 'testament' => 'NT', 'chapters' => 5],
            63 => ['id' => 63, 'name' => 'II João', 'abbreviation' => '2jo', 'testament' => 'NT', 'chapters' => 1],
            64 => ['id' => 64, 'name' => 'III Joao', 'abbreviation' => '3jo', 'testament' => 'NT', 'chapters' => 1],
            65 => ['id' => 65, 'name' => 'Judas', 'abbreviation' => 'jd', 'testament' => 'NT', 'chapters' => 1],
            66 => ['id' => 66, 'name' => 'Apocalipse', 'abbreviation' => 'ap', 'testament' => 'NT', 'chapters' => 22],
        ];
    }

    /**
     * Returns a map of book_id => [chapter_number => verse_count]
     *
     * @return array<int, array<int, int>>
     */
    public function getVersesMap(): array
    {
        return [
            1 => [1 => 31, 2 => 25, 3 => 24, 4 => 26, 5 => 32, 6 => 22, 7 => 24, 8 => 22, 9 => 29, 10 => 32, 11 => 32, 12 => 20, 13 => 18, 14 => 24, 15 => 21, 16 => 16, 17 => 27, 18 => 33, 19 => 38, 20 => 18, 21 => 34, 22 => 24, 23 => 20, 24 => 67, 25 => 34, 26 => 35, 27 => 46, 28 => 22, 29 => 35, 30 => 43, 31 => 55, 32 => 32, 33 => 20, 34 => 31, 35 => 29, 36 => 43, 37 => 36, 38 => 30, 39 => 23, 40 => 23, 41 => 57, 42 => 38, 43 => 34, 44 => 34, 45 => 28, 46 => 34, 47 => 31, 48 => 22, 49 => 33, 50 => 26],
            2 => [1 => 22, 2 => 25, 3 => 22, 4 => 31, 5 => 23, 6 => 30, 7 => 25, 8 => 32, 9 => 35, 10 => 29, 11 => 10, 12 => 51, 13 => 22, 14 => 31, 15 => 27, 16 => 36, 17 => 16, 18 => 27, 19 => 25, 20 => 26, 21 => 36, 22 => 31, 23 => 33, 24 => 18, 25 => 40, 26 => 37, 27 => 21, 28 => 43, 29 => 46, 30 => 38, 31 => 18, 32 => 35, 33 => 23, 34 => 35, 35 => 35, 36 => 38, 37 => 29, 38 => 31, 39 => 43, 40 => 38],
            3 => [1 => 17, 2 => 16, 3 => 17, 4 => 35, 5 => 19, 6 => 30, 7 => 38, 8 => 36, 9 => 24, 10 => 20, 11 => 47, 12 => 8, 13 => 59, 14 => 57, 15 => 33, 16 => 34, 17 => 16, 18 => 30, 19 => 37, 20 => 27, 21 => 24, 22 => 33, 23 => 44, 24 => 23, 25 => 55, 26 => 46, 27 => 34],
            4 => [1 => 54, 2 => 34, 3 => 51, 4 => 49, 5 => 31, 6 => 27, 7 => 89, 8 => 26, 9 => 23, 10 => 36, 11 => 35, 12 => 16, 13 => 33, 14 => 45, 15 => 41, 16 => 50, 17 => 13, 18 => 32, 19 => 22, 20 => 29, 21 => 35, 22 => 41, 23 => 30, 24 => 25, 25 => 18, 26 => 65, 27 => 23, 28 => 31, 29 => 40, 30 => 16, 31 => 54, 32 => 42, 33 => 56, 34 => 29, 35 => 34, 36 => 13],
            5 => [1 => 46, 2 => 37, 3 => 29, 4 => 49, 5 => 33, 6 => 25, 7 => 26, 8 => 20, 9 => 29, 10 => 22, 11 => 32, 12 => 32, 13 => 18, 14 => 29, 15 => 23, 16 => 22, 17 => 20, 18 => 22, 19 => 21, 20 => 20, 21 => 23, 22 => 30, 23 => 25, 24 => 22, 25 => 19, 26 => 19, 27 => 26, 28 => 68, 29 => 29, 30 => 20, 31 => 30, 32 => 52, 33 => 29, 34 => 12],
            6 => [1 => 18, 2 => 24, 3 => 17, 4 => 24, 5 => 15, 6 => 27, 7 => 26, 8 => 35, 9 => 27, 10 => 43, 11 => 23, 12 => 24, 13 => 33, 14 => 15, 15 => 63, 16 => 10, 17 => 18, 18 => 28, 19 => 51, 20 => 9, 21 => 45, 22 => 34, 23 => 16, 24 => 33],
            7 => [1 => 36, 2 => 23, 3 => 31, 4 => 24, 5 => 31, 6 => 40, 7 => 25, 8 => 35, 9 => 57, 10 => 18, 11 => 40, 12 => 15, 13 => 25, 14 => 20, 15 => 20, 16 => 31, 17 => 13, 18 => 31, 19 => 30, 20 => 48, 21 => 25],
            8 => [1 => 22, 2 => 23, 3 => 18, 4 => 22],
            9 => [1 => 28, 2 => 36, 3 => 21, 4 => 22, 5 => 12, 6 => 21, 7 => 17, 8 => 22, 9 => 27, 10 => 27, 11 => 15, 12 => 25, 13 => 23, 14 => 52, 15 => 35, 16 => 23, 17 => 58, 18 => 30, 19 => 24, 20 => 43, 21 => 15, 22 => 23, 23 => 29, 24 => 22, 25 => 44, 26 => 25, 27 => 12, 28 => 25, 29 => 11, 30 => 31, 31 => 13],
            10 => [1 => 27, 2 => 32, 3 => 39, 4 => 12, 5 => 25, 6 => 23, 7 => 29, 8 => 18, 9 => 13, 10 => 19, 11 => 27, 12 => 31, 13 => 39, 14 => 33, 15 => 37, 16 => 23, 17 => 29, 18 => 33, 19 => 43, 20 => 26, 21 => 22, 22 => 51, 23 => 39, 24 => 25],
            11 => [1 => 53, 2 => 46, 3 => 28, 4 => 34, 5 => 18, 6 => 38, 7 => 51, 8 => 66, 9 => 28, 10 => 29, 11 => 43, 12 => 33, 13 => 34, 14 => 31, 15 => 34, 16 => 34, 17 => 24, 18 => 46, 19 => 21, 20 => 43, 21 => 29, 22 => 54],
            12 => [1 => 18, 2 => 25, 3 => 27, 4 => 44, 5 => 27, 6 => 33, 7 => 20, 8 => 29, 9 => 37, 10 => 36, 11 => 21, 12 => 21, 13 => 25, 14 => 29, 15 => 38, 16 => 20, 17 => 41, 18 => 37, 19 => 37, 20 => 21, 21 => 26, 22 => 20, 23 => 37, 24 => 20, 25 => 30],
            13 => [1 => 54, 2 => 55, 3 => 24, 4 => 43, 5 => 26, 6 => 81, 7 => 40, 8 => 40, 9 => 44, 10 => 14, 11 => 47, 12 => 40, 13 => 14, 14 => 17, 15 => 29, 16 => 43, 17 => 27, 18 => 17, 19 => 19, 20 => 8, 21 => 30, 22 => 19, 23 => 32, 24 => 31, 25 => 31, 26 => 32, 27 => 34, 28 => 21, 29 => 30],
            14 => [1 => 17, 2 => 18, 3 => 17, 4 => 22, 5 => 14, 6 => 42, 7 => 22, 8 => 18, 9 => 31, 10 => 19, 11 => 23, 12 => 16, 13 => 22, 14 => 15, 15 => 19, 16 => 14, 17 => 19, 18 => 34, 19 => 11, 20 => 37, 21 => 20, 22 => 12, 23 => 21, 24 => 27, 25 => 28, 26 => 23, 27 => 9, 28 => 27, 29 => 36, 30 => 27, 31 => 21, 32 => 33, 33 => 25, 34 => 33, 35 => 27, 36 => 23],
            15 => [1 => 11, 2 => 70, 3 => 13, 4 => 24, 5 => 17, 6 => 22, 7 => 28, 8 => 36, 9 => 15, 10 => 44],
            16 => [1 => 11, 2 => 20, 3 => 32, 4 => 23, 5 => 19, 6 => 19, 7 => 73, 8 => 18, 9 => 38, 10 => 39, 11 => 36, 12 => 47, 13 => 31],
            17 => [1 => 22, 2 => 23, 3 => 15, 4 => 17, 5 => 14, 6 => 14, 7 => 10, 8 => 17, 9 => 32, 10 => 3],
            18 => [1 => 22, 2 => 13, 3 => 26, 4 => 21, 5 => 27, 6 => 30, 7 => 21, 8 => 22, 9 => 35, 10 => 22, 11 => 20, 12 => 25, 13 => 28, 14 => 22, 15 => 35, 16 => 22, 17 => 16, 18 => 21, 19 => 29, 20 => 29, 21 => 34, 22 => 30, 23 => 17, 24 => 25, 25 => 6, 26 => 14, 27 => 23, 28 => 28, 29 => 25, 30 => 31, 31 => 40, 32 => 22, 33 => 33, 34 => 37, 35 => 16, 36 => 33, 37 => 24, 38 => 41, 39 => 30, 40 => 24, 41 => 34, 42 => 17],
            19 => [1 => 6, 2 => 12, 3 => 8, 4 => 8, 5 => 12, 6 => 10, 7 => 17, 8 => 9, 9 => 20, 10 => 18, 11 => 7, 12 => 8, 13 => 6, 14 => 7, 15 => 5, 16 => 11, 17 => 15, 18 => 50, 19 => 14, 20 => 9, 21 => 13, 22 => 31, 23 => 6, 24 => 10, 25 => 22, 26 => 12, 27 => 14, 28 => 9, 29 => 11, 30 => 12, 31 => 24, 32 => 11, 33 => 22, 34 => 22, 35 => 28, 36 => 12, 37 => 40, 38 => 22, 39 => 13, 40 => 17, 41 => 13, 42 => 11, 43 => 5, 44 => 26, 45 => 17, 46 => 11, 47 => 9, 48 => 14, 49 => 20, 50 => 23, 51 => 19, 52 => 9, 53 => 6, 54 => 7, 55 => 23, 56 => 13, 57 => 11, 58 => 11, 59 => 17, 60 => 12, 61 => 8, 62 => 12, 63 => 11, 64 => 10, 65 => 13, 66 => 20, 67 => 7, 68 => 35, 69 => 36, 70 => 5, 71 => 24, 72 => 20, 73 => 28, 74 => 23, 75 => 10, 76 => 12, 77 => 20, 78 => 72, 79 => 13, 80 => 19, 81 => 16, 82 => 8, 83 => 18, 84 => 12, 85 => 13, 86 => 17, 87 => 7, 88 => 18, 89 => 52, 90 => 17, 91 => 16, 92 => 15, 93 => 5, 94 => 23, 95 => 11, 96 => 13, 97 => 12, 98 => 9, 99 => 9, 100 => 5, 101 => 8, 102 => 28, 103 => 22, 104 => 35, 105 => 45, 106 => 48, 107 => 43, 108 => 13, 109 => 31, 110 => 7, 111 => 10, 112 => 10, 113 => 9, 114 => 8, 115 => 18, 116 => 19, 117 => 2, 118 => 29, 119 => 176, 120 => 7, 121 => 8, 122 => 9, 123 => 4, 124 => 8, 125 => 5, 126 => 6, 127 => 5, 128 => 6, 129 => 8, 130 => 8, 131 => 3, 132 => 18, 133 => 3, 134 => 3, 135 => 21, 136 => 26, 137 => 9, 138 => 8, 139 => 24, 140 => 13, 141 => 10, 142 => 7, 143 => 12, 144 => 15, 145 => 21, 146 => 10, 147 => 20, 148 => 14, 149 => 9, 150 => 6],
            20 => [1 => 33, 2 => 22, 3 => 35, 4 => 27, 5 => 23, 6 => 35, 7 => 27, 8 => 36, 9 => 18, 10 => 32, 11 => 31, 12 => 28, 13 => 25, 14 => 35, 15 => 33, 16 => 33, 17 => 28, 18 => 24, 19 => 29, 20 => 30, 21 => 31, 22 => 29, 23 => 35, 24 => 34, 25 => 28, 26 => 28, 27 => 27, 28 => 28, 29 => 27, 30 => 33, 31 => 31],
            21 => [1 => 18, 2 => 26, 3 => 22, 4 => 16, 5 => 20, 6 => 12, 7 => 29, 8 => 17, 9 => 18, 10 => 20, 11 => 10, 12 => 14],
            22 => [1 => 17, 2 => 17, 3 => 11, 4 => 16, 5 => 16, 6 => 13, 7 => 13, 8 => 14],
            23 => [1 => 31, 2 => 22, 3 => 26, 4 => 6, 5 => 30, 6 => 13, 7 => 25, 8 => 22, 9 => 21, 10 => 34, 11 => 16, 12 => 6, 13 => 22, 14 => 32, 15 => 9, 16 => 14, 17 => 14, 18 => 7, 19 => 25, 20 => 6, 21 => 17, 22 => 25, 23 => 18, 24 => 23, 25 => 12, 26 => 21, 27 => 13, 28 => 29, 29 => 24, 30 => 33, 31 => 9, 32 => 20, 33 => 24, 34 => 17, 35 => 10, 36 => 22, 37 => 38, 38 => 22, 39 => 8, 40 => 31, 41 => 29, 42 => 25, 43 => 28, 44 => 28, 45 => 25, 46 => 13, 47 => 15, 48 => 22, 49 => 26, 50 => 11, 51 => 23, 52 => 15, 53 => 12, 54 => 17, 55 => 13, 56 => 12, 57 => 21, 58 => 14, 59 => 21, 60 => 22, 61 => 11, 62 => 12, 63 => 19, 64 => 12, 65 => 25, 66 => 24],
            24 => [1 => 19, 2 => 37, 3 => 25, 4 => 31, 5 => 31, 6 => 30, 7 => 34, 8 => 22, 9 => 26, 10 => 25, 11 => 23, 12 => 17, 13 => 27, 14 => 22, 15 => 21, 16 => 21, 17 => 27, 18 => 23, 19 => 15, 20 => 18, 21 => 14, 22 => 30, 23 => 40, 24 => 10, 25 => 38, 26 => 24, 27 => 22, 28 => 17, 29 => 32, 30 => 24, 31 => 40, 32 => 44, 33 => 26, 34 => 22, 35 => 19, 36 => 32, 37 => 21, 38 => 28, 39 => 18, 40 => 16, 41 => 18, 42 => 22, 43 => 13, 44 => 30, 45 => 5, 46 => 28, 47 => 7, 48 => 47, 49 => 39, 50 => 46, 51 => 64, 52 => 34],
            25 => [1 => 22, 2 => 22, 3 => 66, 4 => 22, 5 => 22],
            26 => [1 => 28, 2 => 10, 3 => 27, 4 => 17, 5 => 17, 6 => 14, 7 => 27, 8 => 18, 9 => 11, 10 => 22, 11 => 25, 12 => 28, 13 => 23, 14 => 23, 15 => 8, 16 => 63, 17 => 24, 18 => 32, 19 => 14, 20 => 49, 21 => 32, 22 => 31, 23 => 49, 24 => 27, 25 => 17, 26 => 21, 27 => 36, 28 => 26, 29 => 21, 30 => 26, 31 => 18, 32 => 32, 33 => 33, 34 => 31, 35 => 15, 36 => 38, 37 => 28, 38 => 23, 39 => 29, 40 => 49, 41 => 26, 42 => 20, 43 => 27, 44 => 31, 45 => 25, 46 => 24, 47 => 23, 48 => 35],
            27 => [1 => 21, 2 => 49, 3 => 30, 4 => 37, 5 => 31, 6 => 28, 7 => 28, 8 => 27, 9 => 27, 10 => 21, 11 => 45, 12 => 13],
            28 => [1 => 11, 2 => 23, 3 => 5, 4 => 19, 5 => 15, 6 => 11, 7 => 16, 8 => 14, 9 => 17, 10 => 15, 11 => 12, 12 => 14, 13 => 16, 14 => 9],
            29 => [1 => 20, 2 => 32, 3 => 21],
            30 => [1 => 15, 2 => 16, 3 => 15, 4 => 13, 5 => 27, 6 => 14, 7 => 17, 8 => 14, 9 => 15],
            31 => [1 => 21],
            32 => [1 => 17, 2 => 10, 3 => 10, 4 => 11],
            33 => [1 => 16, 2 => 13, 3 => 12, 4 => 13, 5 => 15, 6 => 16, 7 => 20],
            34 => [1 => 15, 2 => 13, 3 => 19],
            35 => [1 => 17, 2 => 20, 3 => 19],
            36 => [1 => 18, 2 => 15, 3 => 20],
            37 => [1 => 15, 2 => 23],
            38 => [1 => 21, 2 => 13, 3 => 10, 4 => 14, 5 => 11, 6 => 15, 7 => 14, 8 => 23, 9 => 17, 10 => 12, 11 => 17, 12 => 14, 13 => 9, 14 => 21],
            39 => [1 => 14, 2 => 17, 3 => 18, 4 => 6],
            40 => [1 => 25, 2 => 23, 3 => 17, 4 => 25, 5 => 48, 6 => 34, 7 => 29, 8 => 34, 9 => 38, 10 => 42, 11 => 30, 12 => 50, 13 => 58, 14 => 36, 15 => 39, 16 => 28, 17 => 27, 18 => 35, 19 => 30, 20 => 34, 21 => 46, 22 => 46, 23 => 39, 24 => 51, 25 => 46, 26 => 75, 27 => 66, 28 => 20],
            41 => [1 => 45, 2 => 28, 3 => 35, 4 => 41, 5 => 43, 6 => 56, 7 => 37, 8 => 38, 9 => 50, 10 => 52, 11 => 33, 12 => 44, 13 => 37, 14 => 72, 15 => 47, 16 => 20],
            42 => [1 => 80, 2 => 52, 3 => 38, 4 => 44, 5 => 39, 6 => 49, 7 => 50, 8 => 56, 9 => 62, 10 => 42, 11 => 54, 12 => 59, 13 => 35, 14 => 35, 15 => 32, 16 => 31, 17 => 37, 18 => 43, 19 => 48, 20 => 47, 21 => 38, 22 => 71, 23 => 56, 24 => 53],
            43 => [1 => 51, 2 => 25, 3 => 36, 4 => 54, 5 => 47, 6 => 71, 7 => 53, 8 => 59, 9 => 41, 10 => 42, 11 => 57, 12 => 50, 13 => 38, 14 => 31, 15 => 27, 16 => 33, 17 => 26, 18 => 40, 19 => 42, 20 => 31, 21 => 25],
            44 => [1 => 26, 2 => 47, 3 => 26, 4 => 37, 5 => 42, 6 => 15, 7 => 60, 8 => 40, 9 => 43, 10 => 48, 11 => 30, 12 => 25, 13 => 52, 14 => 28, 15 => 41, 16 => 40, 17 => 34, 18 => 28, 19 => 41, 20 => 38, 21 => 40, 22 => 30, 23 => 35, 24 => 27, 25 => 27, 26 => 32, 27 => 44, 28 => 31],
            45 => [1 => 32, 2 => 29, 3 => 31, 4 => 25, 5 => 21, 6 => 23, 7 => 25, 8 => 39, 9 => 33, 10 => 21, 11 => 36, 12 => 21, 13 => 14, 14 => 23, 15 => 33, 16 => 27],
            46 => [1 => 31, 2 => 16, 3 => 23, 4 => 21, 5 => 13, 6 => 20, 7 => 40, 8 => 13, 9 => 27, 10 => 33, 11 => 34, 12 => 31, 13 => 13, 14 => 40, 15 => 58, 16 => 24],
            47 => [1 => 24, 2 => 17, 3 => 18, 4 => 18, 5 => 21, 6 => 18, 7 => 16, 8 => 24, 9 => 15, 10 => 18, 11 => 33, 12 => 21, 13 => 13],
            48 => [1 => 24, 2 => 21, 3 => 29, 4 => 31, 5 => 26, 6 => 18],
            49 => [1 => 23, 2 => 22, 3 => 21, 4 => 32, 5 => 33, 6 => 24],
            50 => [1 => 30, 2 => 30, 3 => 21, 4 => 23],
            51 => [1 => 29, 2 => 23, 3 => 25, 4 => 18],
            52 => [1 => 10, 2 => 20, 3 => 13, 4 => 18, 5 => 28],
            53 => [1 => 12, 2 => 17, 3 => 18],
            54 => [1 => 20, 2 => 15, 3 => 16, 4 => 16, 5 => 25, 6 => 21],
            55 => [1 => 18, 2 => 26, 3 => 17, 4 => 22],
            56 => [1 => 16, 2 => 15, 3 => 15],
            57 => [1 => 25],
            58 => [1 => 14, 2 => 18, 3 => 19, 4 => 16, 5 => 14, 6 => 20, 7 => 28, 8 => 13, 9 => 28, 10 => 39, 11 => 40, 12 => 29, 13 => 25],
            59 => [1 => 27, 2 => 26, 3 => 18, 4 => 17, 5 => 20],
            60 => [1 => 25, 2 => 25, 3 => 22, 4 => 19, 5 => 14],
            61 => [1 => 21, 2 => 22, 3 => 18],
            62 => [1 => 10, 2 => 29, 3 => 24, 4 => 21, 5 => 21],
            63 => [1 => 13],
            64 => [1 => 15],
            65 => [1 => 25],
            66 => [1 => 20, 2 => 29, 3 => 22, 4 => 11, 5 => 14, 6 => 17, 7 => 17, 8 => 13, 9 => 21, 10 => 11, 11 => 19, 12 => 18, 13 => 18, 14 => 20, 15 => 8, 16 => 21, 17 => 18, 18 => 24, 19 => 21, 20 => 15, 21 => 27, 22 => 21],
        ];
    }

    /**
     * Returns a map of book_id => ['svg' => string, 'color_light' => string, 'color_dark' => string]
     * SVGs are simple paths (d attribute) or full SVG strings.
     * Colors are hex codes.
     *
     * @return array<int, array{svg: string, color_light: string, color_dark: string}>
     */
    public function getVisualsMap(): array
    {
        return [
            // OLD TESTAMENT (1-39) - Neutral Slate colors
            
            // Pentateuco
            1 => ['svg' => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            2 => ['svg' => '<path d="M6 3v18M18 3v18M2 12h20" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            3 => ['svg' => '<path d="M12 3v18M3 12h18M8 8l8 8M8 16l8-8" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            4 => ['svg' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            5 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],

            // Históricos
            6 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            7 => ['svg' => '<path d="M3 3v18h18" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            8 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            9 => ['svg' => '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            10 => ['svg' => '<path d="M2 12h20M12 2v20" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            11 => ['svg' => '<path d="M3 21h18v-8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8zM12 11V3L3 11h18L12 3z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            12 => ['svg' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 4v16M4 12h16" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            13 => ['svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            14 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            15 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M9 10a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v11H9V10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            16 => ['svg' => '<path d="M3 21h18M4 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M9 21v-8h6v8" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            17 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],

            // Poéticos
            18 => ['svg' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            19 => ['svg' => '<path d="M9 18V5l12-2v13" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="21" cy="16" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            20 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            21 => ['svg' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            22 => ['svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],

            // Profetas Maiores
            23 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            24 => ['svg' => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M8 14s1.5 2 4 2 4-2 4-2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M9 9h.01M15 9h.01" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            25 => ['svg' => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M16 10s-1.5-2-4-2-4 2-4 2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            26 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            27 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],

            // Profetas Menores
            28 => ['svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            29 => ['svg' => '<path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            30 => ['svg' => '<path d="M3 3v18h18" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            31 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            32 => ['svg' => '<path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            33 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            34 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            35 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            36 => ['svg' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            37 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            38 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],
            39 => ['svg' => '<path d="M12 3v18M3 12h18M8 8l8 8M8 16l8-8" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f8fafc', 'color_dark' => '#1e293b'],

            // NEW TESTAMENT (40-66) - Tailwind 100/800 progressive colors by book_order
            
            // Gospels - Red spectrum (40-43)
            40 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fee2e2', 'color_dark' => '#991b1b'], // Mateus - red-100/800
            41 => ['svg' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fed7aa', 'color_dark' => '#9a3412'], // Marcos - orange-200/800
            42 => ['svg' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fef08a', 'color_dark' => '#a16207'], // Lucas - yellow-200/700
            43 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fef9c3', 'color_dark' => '#713f12'], // João - yellow-100/800
            
            // Acts - Amber (44)
            44 => ['svg' => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fef3c7', 'color_dark' => '#78350f'], // Atos - amber-100/900
            
            // Paul's Letters - Green to Cyan spectrum (45-57)
            45 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#dcfce7', 'color_dark' => '#166534'], // Romanos - green-100/800
            46 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#d1fae5', 'color_dark' => '#065f46'], // 1 Coríntios - emerald-100/800
            47 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#ccfbf1', 'color_dark' => '# 115e59'], // 2 Coríntios - teal-100/800
            48 => ['svg' => '<path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 12h20" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#cffafe', 'color_dark' => '#155e75'], // Gálatas - cyan-100/800
            49 => ['svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#e0f2fe', 'color_dark' => '#075985'], // Efésios - sky-100/800
            50 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#dbeafe', 'color_dark' => '#1e40af'], // Filipenses - blue-100/800
            51 => ['svg' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#e0e7ff', 'color_dark' => '#3730a3'], // Colossenses - indigo-100/800
            52 => ['svg' => '<path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#ede9fe', 'color_dark' => '#5b21b6'], // 1 Tessalonicenses - violet-100/800
            53 => ['svg' => '<path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#f3e8ff', 'color_dark' => '#6b21a8'], // 2 Tessalonicenses - purple-100/800
            54 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fae8ff', 'color_dark' => '#701a75'], // 1 Timóteo - fuchsia-100/800
            55 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fce7f3', 'color_dark' => '#831843'], // 2 Timóteo - pink-100/800
            56 => ['svg' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#ffe4e6', 'color_dark' => '#881337'], // Tito - rose-100/800
            57 => ['svg' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.5" fill="none"/><polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="1.5" fill="none"/><line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fef2f2', 'color_dark' => '#7f1d1d'], // Filemom - red-100/800
            
            // General Letters - Warm spectrum (58-65)
            58 => ['svg' => '<path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fff7ed', 'color_dark' => '#7c2d12'], // Hebreus - orange-100/800
            59 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fef3c7', 'color_dark' => '#78350f'], // Tiago - amber-100/900
            60 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#fefce8', 'color_dark' => '#713f12'], // 1 Pedro - yellow-100/800
            61 => ['svg' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#ecfccb', 'color_dark' => '#365314'], // 2 Pedro - lime-100/800
            62 => ['svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#dcfce7', 'color_dark' => '#166534'], // 1 João - green-100/800
            63 => ['svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#d1fae5', 'color_dark' => '#065f46'], // 2 João - emerald-100/800
            64 => ['svg' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#ccfbf1', 'color_dark' => '#115e59'], // 3 João - teal-100/800
            65 => ['svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#cffafe', 'color_dark' => '#155e75'], // Judas - cyan-100/800
            
            // Revelation - Cool spectrum (66)
            66 => ['svg' => '<path d="M12 2l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" fill="none"/>', 'color_light' => '#dbeafe', 'color_dark' => '#1e40af'], // Apocalipse - blue-100/800
        ];
    }
}
