# Plano de Implementação - Sistema de Tradução Bíblica

## 1. Configuração Inicial e Infraestrutura
- [ ] **Configuração do Ambiente**: Verificar PHP 8.2+, Symfony 7, Banco de Dados.
- [ ] **Autenticação**:
    - [ ] Configurar `security.yaml` para login de usuários.
    - [ ] Criar entidade `User` (se não existir) ou adaptar existente.
    - [ ] Criar formulário de login e página de login.
- [ ] **Layout Base**:
    - [ ] Criar estrutura do Dashboard (sidebar, header).
    - [ ] Integrar Tailwind CSS.

## 2. Modelagem de Dados (Entidades)
Criar as entidades Doctrine revisadas:
- [ ] `Testament` (Testamento): nome (Novo/Velho).
- [ ] `Book` (Livro): nome, abreviação, FK `testament`, ordem.
- [ ] `BibleVersion` (Versão): nome, abreviação (ex: Almeida, Nova Tradução, ).
- [ ] `Verse` (Versículo Canônico):
    - [ ] FK `book`
    - [ ] `chapter` (int)
    - [ ] `verse` (int)
- [ ] `VerseText` (Texto do Versículo):
    - [ ] FK `verse`
    - [ ] FK `version`
    - [ ] FK `user` (nullable, para traduções oficiais)
    - [ ] `text` (longtext)
    - [ ] `title` (string, nullable) - Título/Assunto
- [ ] `TranslationHistory` (Histórico):
    - [ ] FK `verse_text`
    - [ ] `old_text`
    - [ ] FK `user`
    - [ ] `created_at`
- [ ] `StrongDefinition`: codigo, palavra em hebreu, palavra em grego, translitreal, definicao completa.
- [ ] `VerseWord` (Interlinear): FK `verse`, FK `strong`, palavra em hebreu, palavra em grego, translitreal, english_type, type_portuguese, tradução.
- [ ] `Reference` (Referências):
    - [ ] `VerseReference`: FK `verse`, term, reference_text.
    - [ ] `GlobalReference`: term, reference_text.

## 3. Importação de Dados
- [ ] Criar comando `app:import-legacy`.
- [ ] Importar `biblia_testament` -> `Testament`.
- [ ] Importar `biblia_book` -> `Book`.
- [ ] Importar `biblia_version` -> `BibleVersion`.
- [ ] Importar `biblia_verse_ext` -> `Verse`.
- [ ] Importar `biblia_verse` -> `VerseText`.
- [ ] Importar `strongs` -> `StrongDefinition`.
- [ ] Importar `interlinear` -> `VerseWord`.

## 4. Funcionalidades do Sistema

### 4.1. Dashboard e Seleção
- [ ] **Lista de Livros**:
    - [ ] Query para calcular % de conclusão (baseado em `VerseText` da versão "Nova Tradução").
- [ ] **Lista de Capítulos**:
    - [ ] Listar capítulos com status.

### 4.2. Interface de Tradução
- [ ] **Visualização**:
    - [ ] Carregar `Verse` e seus `VerseText` (Almeida vs Nova Tradução).
- [ ] **Edição**:
    - [ ] Formulário editando `VerseText` da versão "Nova Tradução".
    - [ ] Ao salvar:
        - [ ] Verificar se mudou.
        - [ ] Se mudou, criar registro em `TranslationHistory`.
        - [ ] Atualizar `VerseText`.

### 4.3. Sistema de Abas (Tabs)
- [ ] **Aba 1: Capítulo Original**:
    - [ ] Tabela comparativa (Grego | Almeida | Nova Tradução).
- [ ] **Abas Dinâmicas (Strongs)**:
    - [ ] Usar `VerseWord` para identificar palavras e links.

### 4.4. Gestão de Referências
- [ ] CRUD de referências.

## 5. Interface e UX
- [ ] Estilização com Tailwind CSS.
- [ ] Stimulus para interatividade.
