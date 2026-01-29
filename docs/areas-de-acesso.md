# Documentação Funcional: Áreas de Acesso, Usuários e Mensageria

Este documento detalha o funcionamento atual do sistema, cobrindo hierarquia de usuários, permissões, fluxos de trabalho (tradução e revisão), sistema de aprovações visual e mensageria.

---

## 1. Usuários e Hierarquia (Grupos de Trabalho)

O sistema utiliza um campo `workGroup` (inteiro) na entidade Usuário para definir permissões e papéis. Não há roles complexas do Symfony além de `ROLE_USER` e `ROLE_ADMIN` (para o Grupo 0).

### Grupo 0: Administrador (Root)
*   **Permissão:** Acesso irrestrito a todo o sistema.
*   **Visibilidade:** Pode ver todos os usuários, todas as mensagens, todos os paratextos e editar qualquer tradução.
*   **Painel:** Visualiza estatísticas globais e todas as conversas do sistema.

### Grupo 1: Tradutor
*   **Responsabilidade:** Produzir a tradução alvo (Ex: Versão Haroldo Dutra).
*   **Acesso:**
    *   Painéis de Tradução (Leitura e Edição).
    *   Pode editar o texto e título dos versículos.
    *   Recebe mensagens de Revisores.
*   **Fluxo:** Escreve a tradução -> Aguarda revisão -> Recebe feedback via Mensagens.

### Grupo 2: Revisor de Tradução
*   **Responsabilidade:** Conferir o trabalho do Tradutor.
*   **Acesso:**
    *   Painéis de Tradução (Somente Leitura). **Não pode editar o texto.**
    *   Pode "Aprovar" (Marcar como revisado) versículos individualmente.
    *   Pode enviar mensagens (comentários) vinculados a versículos.
*   **Restrição de Mensagem:** Ao enviar mensagens, o sistema limita os destinatários apenas aos usuários do **Grupo 1 (Tradutores)**.

### Grupo 3: Autor de Paratextos
*   **Responsabilidade:** Criar conteúdo extra-bíblico (Intruduções, Notas Históricas, Mapas).
*   **Acesso:**
    *   CRUD de Paratextos.
    *   Dashboard exibe "Meus Paratextos" (filtrado pelo autor).
*   **Restrição:** Visualiza e edita apenas os conteúdos criados por ele mesmo.

### Grupo 4: Revisor de Paratextos
*   **Responsabilidade:** Revisar conteúdos de paratexto.
*   **Acesso:**
    *   Visualização de Paratextos (Somente Leitura).
    *   Dashboard exibe "Paratextos Recentes" de todos os autores.
    *   Pode enviar mensagens para os Autores (Grupo 3).

---

## 2. Áreas do Sistema e Navegação

### 2.1 Dashboard (`/admin`)
Ponto de entrada do sistema. Exibe widgets contextuais baseados no grupo:
*   **Progresso Global:** Gráficos de completude da tradução por livro.
*   **Mensagens:** Widget de mensagens não lidas ou lista de conversas ativas (para Admin/Translator).

### 2.4 Notificações (Sineta)
*   **Localização:** Canto superior direito do cabeçalho (Header).
*   **Funcionalidade:**
    *   Exibe um ícone de sino (`sl-icon-button name="bell"`).
    *   Possui um **Badge Vermelho** (`sl-badge`) indicando o número total de mensagens **não lidas**.
    *   Ao clicar, redireciona para a lista de mensagens (`/admin/message/`), já filtrando as pendências.
*   **Visibilidade:** Visível em todas as páginas da área administrativa (`/admin/*`).

### 2.2 Área de Tradução (Leitura Contínua)
*   **Rota:** `/admin/translation/{book}/{chapter}`
*   **Visual:** Layout que simula uma página de livro/bíblia impressa.
*   **Funcionalidades:**
    *   **Navegação:** Paginação de capítulos no topo.
    *   **Edição (Grupo 0/1):** Clicar no número do versículo abre o modo de edição detalhada.
    *   **Revisão (Toggle):** Ícone de "Check" (Círculo) ao lado do versículo.
    *   **Comentários:** Ícone de chat para iniciar uma thread sobre aquele versículo específico.

### 2.3 Área de Detalhe do Versículo (Interlinear/Edição)
*   **Rota:** `/admin/translation/{book}/{chapter}/{verseNum}`
*   **Visual:** Tabela comparativa e Interlinear.
*   **Interlinear:** Exibe Palavra Original (Hebraico/Grego), Transliteração, Definição Strong, e equivalentes em Inglês/Almeida.
*   **Comparativo:** Mostra o versículo em múltiplas versões lado a lado (Original, Almeida, Alvo).

---

## 3. Sistema de Aprovação e Cores (Visual Heatmap)

O sistema visualiza o nível de confiabilidade/revisão de um versículo através de uma escala de cores de fundo (background color). Quanto mais revisores aprovarem um versículo, mais escura a cor verde se torna.

### Lógica de Aprovação
*   Um usuário só pode aprovar um versículo uma única vez.
*   A contagem (`reviewCounts`) soma todas as aprovações únicas naquele versículo.

### Escala de Cores (CSS Classes Tailwind)

| Qtd. Aprovações | Cor Visual | Classe CSS | Descrição |
| :--- | :--- | :--- | :--- |
| **0** | Branco/Transparente | `bg-white` ou nula | Nenhuma revisão. |
| **1** | Verde Muito Claro | `bg-green-50` | Início do processo. |
| **2** | Verde Claro | `bg-green-100` | |
| **3** | Verde Suave | `bg-green-200` | |
| **4** | Verde Médio-Claro | `bg-green-300` | |
| **5** | Verde Médio | `bg-green-400` | |
| **6** | Verde Vibrante | `bg-green-500` | Ponta da escala média. |
| **7** | Verde Escuro Suave | `bg-green-600` | |
| **8** | Verde Escuro | `bg-green-700` | |
| **9** | Verde Muito Escuro | `bg-green-800` | |
| **10** | Verde Intenso | `bg-green-900` | Alta confiabilidade. |
| **11+** | Verde Quase Preto | `bg-green-950` | Consenso absoluto (Máximo). |

> **Nota UI:** No layout de "livro", o texto do versículo recebe essa cor de fundo. Se for o primeiro versículo do bloco, o número do capítulo também recebe a cor.

---

## 4. Sistema de Mensageria (Estilo WhatsApp)

O sistema possui um chat interno contextual para comunicação entre Tradutores, Autores e Revisores.

### 4.1 Estrutura da Mensagem (`Message` Entity)
*   **Remetente/Destinatário:** Usuários do sistema.
*   **Contexto:** Mensagens podem ser vinculadas a:
    *   `translation` (Versículo específico)
    *   `paratext` (Paratexto específico)
*   **Status de Leitura:** `unread`, `read`, `ignored`, `replied`, `resolved`.
*   **Threading:** Suporta respostas (Replies). O sistema exibe a conversa linearmente ordenada por data.

### 4.2 Interface de Usuário (Chat UI)
A interface imita aplicativos de mensagem modernos (como WhatsApp/Telegram) para familiaridade.

#### Mensagens Enviadas (Eu)
*   **Alinhamento:** Direita (`justify-end`).
*   **Estilo:** Balão Azul (`bg-blue-600`), Texto Branco.
*   **Borda:** Arredondada, ponta superior direita reta (`rounded-tr-none`).
*   **Indicadores de Status:**
    *   Icone `check` (Cinza): Enviada.
    *   Icone `check-all` (Azul/Colorido): Lida, Respondida ou Resolvida.

#### Mensagens Recebidas (Outros)
*   **Alinhamento:** Esquerda (`justify-start`).
*   **Estilo:** Balão Branco (`bg-white`), Texto Escuro (`text-slate-800`), Borda Cinza.
*   **Borda:** Arredondada, ponta superior esquerda reta (`rounded-tl-none`).
*   **Cabeçalho:** Nome do remetente em destaque.

### 4.3 Fluxo de Interação
1.  **Iniciar:** Usuário clica no ícone de chat em um versículo. O modal abre já com o contexto preenchido.
2.  **Responder:** No rodapé do modal de leitura, há um campo de texto para resposta rápida.
3.  **Encadeamento:** Respostas são tecnicamente "filhas" da mensagem original, mas visualmente apresentadas em flat-list cronológica para fluidez de leitura.

### 4.4 Gerenciamento de Mensagens (Painel Admin)
*   **Acesso:** Rota `/admin/message/`.
*   **Filtros Disponíveis:**
    *   **Não Resolvidos:** (Padrão) Exibe mensagens `unread`, `read`, `replied` ou `ignored`.
    *   **Resolvida:** Exibe apenas mensagens com status `resolved`.
    *   **Todos:** Exibe todo o histórico de mensagens.
*   **Lista de Conversas:**
    *   Agrupa mensagens por "thread" (conversa).
    *   Exibe contador de mensagens não lidas (Badge verde).
    *   Botão rápido para marcar conversa como **Resolvida**.

### 4.5 Status da Mensagem (`Message` Entity)
Os status controlam o fluxo de vida de uma mensagem:
1.  **unread:** Status inicial ao enviar.
2.  **read:** Quando o destinatário visualiza a mensagem.
3.  **replied:** Quando o destinatário responde.
4.  **ignored:** Pode ser usado para arquivar sem resolver (funcionalidade opcional).

---

## 6. Gestão de Paratextos (`/admin/paratext`)

Módulo dedicado ao conteúdo complementar da bíblia (Notas, Mapas, Introduções).

### 6.1 Tipos de Conteúdo Suportados
O sistema categoriza os paratextos para organização e formatação futura:
*   **Introduções:** `Introdução por Livro`, `Introdução de Capítulo`.
*   **Notas:** `Nota de Perícope`, `Nota Geográfica`, `Nota Histórica`, `Nota de Personagem`, `Nota Cultural`, `Nota Explicativa`.
*   **Visuais/Outros:** `Mapa`, `Linha do Tempo`, `Árvore Genealógica`, `Artigo`.

### 6.2 Visualização e Listagem
A listagem de paratextos segue o mesmo padrão visual de **Heatmap de Aprovações** da tradução:
*   **Linhas da Tabela:** A linha inteira do registro muda de cor (escala verde) conforme o número de aprovações (`reviewCounts`).
*   isso permite identificar rapidamente quais conteúdos já estão maduros/revisados e quais precisam de atenção.
*   **Colunas:** ID, Título, Tipo (Badge), Referência Bíblica (Livro/Cap/Ver), Autor e Ações.

### 6.3 Fluxo de Criação e Revisão
1.  **Criação (Autor - Grupo 3):**
    *   Preenche Título, Tipo e Conteúdo (Editor Rico/TinyMCE).
    *   Pode vincular a um Livro, Capítulo e Versículo específico.
    *   Pode fazer upload de Imagem de destaque.
2.  **Revisão (Revisor - Grupo 4):**
    *   Visualiza o conteúdo em modo de leitura (`/admin/paratext/{id}`).
    *   Pode enviar mensagem direta ao Autor para solicitar correções.
    *   Pode marcar como "Revisado" (Aprovar), incrementando o contador e alterando a cor na lista.

---

## 5. Resumo Técnico dos Processos

### Processo: Revisão de Tradução
1.  **Revisor (Grupo 2)** acessa `/admin/translation/{book}/{chapter}`.
2.  Lê o texto. Se estiver correto:
    *   Clica no **Ícone de Check**.
    *   O sistema salva um `VerseReview`.
    *   A página recarrega (ou atualiza via JS) e a cor de fundo do versículo escurece (ex: muda de `bg-green-100` para `bg-green-200`).
3.  Se houver erro/dúvida:
    *   Clica no **Ícone de Chat**.
    *   Escreve mensagem para o Tradutor.
    *   Tradutor recebe notificação no Dashboard.

### Processo: Criação de Paratexto
1.  **Autor (Grupo 3)** acessa admin e cria novo Paratexto.
2.  Salva conteúdo.
3.  **Revisor de Paratexto (Grupo 4)** vê o item na lista "Recentes".
4.  Revisor lê e pode enviar mensagem ao Autor caso precise de ajustes.