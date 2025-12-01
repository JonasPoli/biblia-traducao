# Mapa de Importação de Dados

Este documento mapeia os dados do sistema legado (`nepe`) para o novo sistema de tradução.

## 1. Livros (Books)
**Origem**: Tabela `biblia_book`
**Destino**: Entidade `Book`

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | Manter ID para integridade |
| `name` | `name` | Nome do livro |
| `abbreviation` | `abbreviation` | Abreviatura (ex: Mt) |
| `testament_id` | `testament_id` | FK para Testament |
| `book_order` | `bookOrder` | Ordem canônica |

## 1.1. Testamentos (Testaments)
**Origem**: Tabela `biblia_testament`
**Destino**: Entidade `Testament`

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | |
| `name` | `name` | Novo / Velho |

## 2. Estrutura de Versículos (Referência Canônica)
**Origem**: Tabela `biblia_verse_ext`
**Destino**: Entidade `Verse` (Estrutural)

Esta tabela define a existência do versículo, independente da tradução.

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | ID Canônico do Versículo |
| `book_id` | `book_id` | FK para Book |
| `chapter` | `chapter` | Número do Capítulo |
| `verse` | `verse` | Número do Versículo |

## 3. Versões da Bíblia
**Origem**: Tabela `biblia_version`
**Destino**: Entidade `BibleVersion`

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | |
| `name` | `name` | Ex: Almeida Corrigida Fiel |
| `abbreviation` | `abbreviation` | Ex: ACF |

## 4. Texto dos Versículos (Traduções)
**Origem**: Tabela `biblia_verse`
**Destino**: Entidade `VerseText` (ou `Translation`)

Esta tabela contém o texto real. Um `Verse` (canônico) pode ter múltiplos `VerseText` (um para cada versão).

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | |
| `external_id_id` | `verse_id` | FK para o Versículo Canônico (Verse) |
| `version_id` | `version_id` | FK para a Versão (BibleVersion) |
| `text` | `text` | O texto do versículo |
| `subject` | `subject` | Título/Assunto do versículo (ou seção) |
| `user_id` | `user_id` | Quem criou/editou (importante para nova tradução) |

> **Estratégia de Nova Tradução**:
> Criaremos uma nova entrada em `BibleVersion` para o projeto atual.
> As novas traduções serão salvas na tabela equivalente a `biblia_verse` (ex: `VerseText`), vinculadas a essa nova versão e ao usuário logado.

## 5. Histórico de Alterações (Audit)
**Novo Requisito**: Tabela de histórico para quando uma tradução for alterada.
**Destino**: Entidade `TranslationHistory`

| Coluna | Descrição |
| :--- | :--- |
| `id` | PK |
| `verse_text_id` | FK para a tradução que foi alterada |
| `old_text` | O texto anterior |
| `user_id` | Quem fez a alteração |
| `created_at` | Data da alteração |

## 6. Dicionário Strong e Interlinear
Mantém-se conforme análise anterior, mas vinculando ao `biblia_verse_ext` (Verse Canônico).

**Origem**: `strong_dictionary`
**Destino**: `StrongDefinition`

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `id` | `id` | PK |
| `strong_code` | `code` | Provável código (ex: G123) |
| `definition` | `definition` | Definição curta |
| `lexame` | `lemma` | Palavra base |
| `transliteration` | `transliteration` | |
| `pronunciation` | `pronunciation` | |
| `text` | `fullDefinition` | Definição completa/detalhada |
| `greek_word` | `greekWord` | |
| `hebrew_word` | `hebrewWord` | |
| `transliteral` | `transliteral` | |

**Origem**: `interlinear`
**Destino**: `VerseWord`

| Coluna Origem | Coluna Destino | Obs |
| :--- | :--- | :--- |
| `external_id_id` | `verse_id` | FK para Verse (biblia_verse_ext) |
| `strong_id` | `strong_code` | Link para definição Strong |
| `greek_word` | `greekWord` | Palavra no texto original (Grego) |
| `hebrew_word` | `hebrewWord` | Palavra no texto original (Hebraico) |
| `portuguese_word` | `wordPt` | Tradução literal |
| `transliteral` | `transliteration` | |
| `english_type` | `englishType` | |
| `portuguese_type` | `portugueseType` | |

## Resumo da Migração Revisado

1.  **Books**: `biblia_book` -> `Book`
2.  **Versions**: `biblia_version` -> `BibleVersion`
3.  **Verses (Canônico)**: `biblia_verse_ext` -> `Verse` (Define a estrutura Cap/Verso)
4.  **Texts**: `biblia_verse` -> `VerseText` (Vincula Verse + Version + Texto)
5.  **Strongs/Interlinear**: `interlinear` -> `VerseWord` (Vincula Verse + Strong)
