# Sistema de Mensagens e Controle de Acesso

Este documento detalha a implementação do sistema de usuários, hierarquia de acesso (WorkGroups) e o novo módulo de mensagens (estilo WhatsApp).

## 1. Usuários e Hierarquia de Acesso

O sistema utiliza a entidade `User` com um campo `workGroup` (integer) para definir perfis de atuação.

### WorkGroups (Grupos de Trabalho)

| ID | Perfil | Descrição e Permissões |
| :--- | :--- | :--- |
| **0** | **Administrador** | Acesso total ao sistema. Pode ver todas as mensagens e gerenciar todos os conteúdos. |
| **1** | **Tradutor** | Pode traduzir versículos atribuídos. Não modera paratextos. |
| **2** | **Revisor de Tradução** | Revisa traduções. |
| **3** | **Autor de Paratextos** | Cria e edita conteúdos extras (mapas, artigos). |
| **4** | **Revisor de Paratextos** | Revisa e comenta paratextos. Único (além do Admin) com permissão para adicionar comentários em paratextos. |

### Lógica de Permissões Específicas
- **Comentários em Paratextos**:
  - Restrito a usuários com `workGroup == 4` (Revisor) ou `workGroup == 0` (Admin).
  - Autores (`workGroup == 3`) podem visualizar mas não iniciar threads de revisão em seus próprios textos (regra aplicada no template).

---

## 2. Sistema de Mensagens

O módulo de mensagens foi refatorado para funcionar como um chat (estilo WhatsApp/Gmail), agrupado por conversa (Thread).

### Estrutura de Dados
- **Entidade**: `Message`
- **Auto-relacionamento**: `parent` (ManyToOne) liga uma resposta à mensagem original.
- **Root Message**: A mensagem inicial (`parent` é nulo) define a "Conversa".
- **Contexto**: Mensagens podem estar atreladas a um contexto (`contextType` = 'paratext', 'translation') e um ID.

### Funcionamento da Interface (WhatsApp Style)
1. **Lista de Conversas (`/admin/message/`)**:
   - Exibe a lista de conversas ordenadas pela **última interação** (envio ou recebimento).
   - Mostra o nome do contato, trecho da última mensagem, data/hora e indicador de mensagens não lidas.
   - **Indicadores Visuais**:
     - **Bolinha Verde**: Novas mensagens não lidas.
     - **Cinza/Opacidade**: Conversas marcadas como "Resolvidas".

2. **Visualização da Conversa (Modal)**:
   - Ao clicar em uma conversa, abre-se um modal com todo o histórico (Thread) ordenado cronologicamente.
   - **Balões de Mensagem**:
     - Direita (Azul): Mensagens enviadas pelo usuário.
     - Esquerda (Cinza): Mensagens recebidas.
   - **Status de Leitura**: Ícones de check (visto/enviado) para mensagens do usuário.
   - **Responder**: Formulário fixo no rodapé do modal para envio rápido.

3. **Lógica de Status**:
   - `unread`: Nova mensagem.
   - `read`: Visualizada pelo destinatário.
   - `replied`: Respondida.
   - `resolved`: Tópico encerrado (fica cinza na lista).
   - **Reativação**: Se uma nova mensagem é enviada em uma thread "Resolvida", o status volta para `replied` e a conversa volta a ficar ativa (cor normal).

### Repositório (`MessageRepository`)
- `findConversations(User $user)`: Busca Threads (mensagens raiz) onde o usuário é remetente ou destinatário (direto ou em respostas), ordenadas pela data da última resposta (`MAX(replies.sentAt)`).

---

## 3. Resumo Técnico para Continuidade

- **Controller**: `App\Controller\Admin\MessageController`
- **Templates**:
  - `templates/admin/message/index.html.twig`: Lista principal (estilo WhatsApp).
  - `templates/admin/message/_read_modal.html.twig`: Modal de chat.
- **Service**: `App\Service\MessageService`
  - Centraliza a lógica de envio e reset de status de threads resolvidas.
