# Sistema de Mensagens

Este documento detalha a implementação completa do módulo de mensagens do sistema (estilo WhatsApp/Gmail), incluindo entidades, serviços, rotas, templates e a lógica de controle de acesso por `workGroup`.

---

## 1. Visão Geral da Arquitetura

O sistema funciona com **threads de conversa** baseadas em auto-relacionamento (parent/replies). Cada conversa tem uma **mensagem raiz** (`parent = null`) que carrega o assunto e o status geral. As respostas são mensagens filhas ligadas à raiz via campo `parent`.

```
Message (raiz, parent=null)
 ├── status: 'unread' | 'read' | 'replied' | 'resolved' | 'ignored'
 └── replies: Message[]
      ├── Message (reply 1)
      └── Message (reply 2)
```

---

## 2. Entidade `Message`

**Arquivo:** `src/Entity/Message.php`

| Campo       | Tipo                    | Obrigatório | Descrição                                            |
| :---------- | :---------------------- | :---------- | :--------------------------------------------------- |
| `id`        | `int`                   | sim         | PK auto-gerado                                       |
| `sender`    | `ManyToOne → User`      | sim         | Quem enviou                                          |
| `recipient` | `ManyToOne → User`      | sim         | Quem recebe                                          |
| `subject`   | `string(255)\|null`     | não         | Assunto da mensagem raiz. Respostas recebem `Re: ...` |
| `content`   | `text`                  | sim         | Corpo da mensagem                                    |
| `sentAt`    | `DateTimeImmutable`     | sim         | Definido automaticamente no `__construct()`          |
| `readAt`    | `DateTimeImmutable\|null` | não       | Momento em que foi lida (null = não lida)            |
| `status`    | `string(20)`            | sim         | Estado da mensagem (ver tabela abaixo). Default: `unread` |
| `contextType` | `string(50)\|null`   | não         | Tipo de contexto: `'translation'`, `'paratext'`      |
| `contextId` | `json\|null`            | não         | ID(s) do contexto (ex: `{"id": 42}`)                |
| `parent`    | `ManyToOne → Message\|null` | não    | Liga resposta à mensagem pai. Nulo = mensagem raiz   |
| `replies`   | `OneToMany → Message[]` | —           | Coleção de respostas a esta mensagem                 |

### Ciclo de Status

| Status     | Significado                                                   |
| :--------- | :------------------------------------------------------------ |
| `unread`   | Enviada, não lida pelo destinatário                           |
| `read`     | Lida pelo destinatário (define `readAt`)                      |
| `replied`  | O destinatário (ou o remetente) enviou uma resposta na thread |
| `ignored`  | Marcada como ignorada (via ação manual)                       |
| `resolved` | Conversa encerrada (fica cinza na lista)                      |

> **Regra de reativação:** Se uma nova mensagem for enviada em uma thread cujo status da raiz é `resolved`, `read` ou `unread`, o status da raiz é automaticamente revertido para `replied`.

---

## 3. Serviço `MessageService`

**Arquivo:** `src/Service/MessageService.php`

Centraliza toda a lógica de negócio. É injetado no `MessageController`.

### `sendMessage(User $recipient, string $content, ...)` → `Message`

Cria e persiste uma nova mensagem. Parâmetros opcionais:

| Parâmetro    | Tipo               | Descrição                                     |
| :----------- | :----------------- | :-------------------------------------------- |
| `$recipient` | `User`             | Destinatário                                  |
| `$content`   | `string`           | Corpo da mensagem                             |
| `$subject`   | `?string`          | Assunto (respostas auto-preenchem `Re: ...`)  |
| `$contextType` | `?string`        | Tipo de contexto ('translation', 'paratext')  |
| `$contextId` | `?array`           | ID do contexto em formato array               |
| `$parent`    | `?Message`         | Mensagem pai (null = nova conversa)           |

**Lógica de reply:**
1. Define o assunto como `Re: {assunto original}`.
2. Navega até a **mensagem raiz** da thread (percorre `getParent()` recursivamente).
3. Se a raiz estava `resolved`, `read` ou `unread`, muda para `replied`.
4. Também marca o `$parent` imediato como `replied`.

### Outros métodos do serviço

| Método                          | Descrição                                                           |
| :------------------------------ | :------------------------------------------------------------------ |
| `markAsRead(Message $message)`  | Define `readAt` e muda status para `read` (apenas se ainda `unread`) |
| `markAsIgnored(Message $message)` | Muda status para `ignored`                                        |
| `markAsResolved(Message $message)` | Muda status para `resolved`                                      |
| `getUnreadCount(User $user)`    | Retorna o total de mensagens com status `unread` para o usuário      |

---

## 4. Repositório `MessageRepository`

**Arquivo:** `src/Repository/MessageRepository.php`

| Método                                  | Descrição                                                                                    |
| :-------------------------------------- | :------------------------------------------------------------------------------------------- |
| `findUnreadByUser(User $user)`          | Retorna mensagens com status `unread` onde o usuário é destinatário                          |
| `findByUser(User $user)`                | Retorna todas as mensagens onde o usuário é remetente ou destinatário                        |
| `findInboxThreads(User $user)`          | Retorna threads raiz onde o usuário é destinatário (direto ou nas replies). Legado.          |
| `findSentThreads(User $user)`           | Retorna threads raiz onde o usuário é remetente. Legado.                                     |
| `findConversations(User $user, ?string $status)` | **Principal.** Retorna threads raiz do usuário, ordenadas pela data da última interação. Aceita filtro por status. Default exclui `resolved`. |
| `findAllConversations(?string $status)` | **Somente Admin.** Igual ao anterior, mas sem filtro por usuário (vê todas as conversas).    |

### Lógica de ordenação (ambos os métodos principais)

```sql
ORDER BY CASE WHEN MAX(replies.sentAt) IS NULL THEN root.sentAt ELSE MAX(replies.sentAt) END DESC
```
→ Conversas com resposta mais recente aparecem no topo, mesmo que a mensagem raiz seja antiga.

### Comportamento do filtro `$status`

| Valor         | Resultado                                               |
| :------------ | :------------------------------------------------------ |
| `null`        | Exibe: `unread`, `read`, `ignored`, `replied` (sem `resolved`) |
| `'unread'`    | Apenas raízes com status `unread`                       |
| `'resolved'`  | Apenas raízes com status `resolved`                     |
| `'all'`       | Sem filtro de status                                    |
| outro valor   | Filtra pelo valor exato no campo `status` da raiz       |

---

## 5. Controller `MessageController`

**Arquivo:** `src/Controller/Admin/MessageController.php`  
**Prefixo da rota:** `/admin/message`  
**Guard:** `#[IsGranted('ROLE_USER')]`

### Rotas disponíveis

| Rota                              | Método | Nome Symfony                  | Descrição                                              |
| :-------------------------------- | :----- | :---------------------------- | :----------------------------------------------------- |
| `GET /admin/message/widget`       | GET    | `app_admin_message_widget`    | Retorna badge com contagem de não lidas (para o header) |
| `GET /admin/message/`             | GET    | `app_admin_message_index`     | Lista de conversas com filtros                         |
| `GET /admin/message/{id}`         | GET    | `app_admin_message_read`      | Retorna HTML do modal de conversa (fragment)            |
| `POST /admin/message/send`        | POST   | `app_admin_message_send`      | Cria nova conversa (JSON body)                         |
| `POST /admin/message/{id}/reply`  | POST   | `app_admin_message_reply`     | Responde a uma conversa existente (form POST)          |
| `POST /admin/message/{id}/status/{status}` | POST | `app_admin_message_status` | Altera o status de uma mensagem (JSON response)       |

### Lógica de carregamento da thread (`read` action)

```php
// 1. Navega até a raiz
$root = $message;
while ($root->getParent()) { $root = $root->getParent(); }

// 2. Coleta recursivamente todas as replies
$conversation = [$root];
$this->collectReplies($root, $conversation);

// 3. Ordena cronologicamente
usort($conversation, fn($a, $b) => $a->getSentAt() <=> $b->getSentAt());

// 4. Marca como lida (apenas mensagens recebidas pelo user)
foreach ($conversation as $msg) {
    if ($msg->getRecipient() === $user && $msg->getStatus() === 'unread') {
        $messageService->markAsRead($msg);
    }
}
```

### Lógica de permissão por `workGroup`

| workGroup | Papel                  | Permissão no sistema de mensagens                       |
| :-------- | :--------------------- | :------------------------------------------------------ |
| `0`       | **Administrador**      | Vê **todas** as conversas via `findAllConversations()`  |
| `1-4`     | Demais usuários        | Vê apenas suas próprias conversas via `findConversations()` |

O `workGroup == 0` também tem a role `ROLE_ADMIN` automaticamente (via `getRoles()` na entidade `User`).

### Envio de nova mensagem (JSON — rota `/send`)

```json
POST /admin/message/send
Content-Type: application/json

{
  "recipient_id": 5,
  "content": "Olá, preciso de ajuda com o versículo X.",
  "subject": "Dúvida sobre tradução",
  "context_type": "translation",
  "context_id": {"book": 1, "chapter": 3, "verse": 16},
  "parent_id": null
}
```

> **Nota:** O campo `parent_id` está presente no código mas ainda **não é processado** totalmente (comentário `TODO` no controller). Para respostas, usar a rota `/{id}/reply`.

---

## 6. Templates

**Diretório:** `templates/admin/message/`

| Template               | Função                                                                     |
| :--------------------- | :------------------------------------------------------------------------- |
| `index.html.twig`      | Página principal da lista de conversas (estilo WhatsApp)                   |
| `_read_modal.html.twig` | Fragment HTML carregado via `fetch()` — exibe o histórico em balões       |
| `_widget.html.twig`    | Badge de notificação do header (badge vermelho com contagem de não lidas)  |
| `_list_table.html.twig` | Template auxiliar (tabela de listagem — uso legado ou alternativo)        |

### `index.html.twig` — Detalhes

- **Filtros de status** no topo: "Não Resolvidos" (padrão) | "Resolvida" | "Todos"
- **Auto-abertura de modal**: se a URL contiver `?open={id}`, o modal abre automaticamente via `DOMContentLoaded`.
- **Avatar duplo (Admin):** Admin vê os dois avatars (remetente + destinatário) em sobreposição.
- **Unread badge:** bolinha verde no avatar com contagem de mensagens não lidas da thread.
- **Ação "Resolver":** botão verde por conversa → chama `POST /{id}/status/resolved` via `fetch()` sem recarregar.

### `_read_modal.html.twig` — Detalhes

- **Balões de mensagem:**
  - **Direita (azul):** mensagens enviadas pelo usuário logado (`isMe = true`)
  - **Esquerda (branco/cinza):** mensagens recebidas
- **Indicadores de leitura** (apenas para mensagens enviadas):
  - `check-all` (azul): status é `read`, `replied` ou `resolved`
  - `check` (cinza): status `unread` = mensagem enviada mas ainda não lida
- **Footer de resposta:** formulário com `textarea` que envia para `POST /{id}/reply` (onde `{id}` é o ID da **última mensagem** da conversa: `conversation|last.id`)
- **Header do modal:** exibe `rootMessage.subject`, `rootMessage.contextType` (se existir) e data de início.

---

## 7. Widget de Notificação

O badge de mensagens no header do admin é carregado na rota `GET /admin/message/widget` e renderiza `_widget.html.twig`. Mostra um `sl-badge` vermelho com a contagem de mensagens `unread` para o usuário logado. Se não houver mensagens não lidas, não renderiza nada.

---

## 8. Referência de Arquivos

| Caminho                                                  | Tipo       |
| :------------------------------------------------------- | :--------- |
| `src/Entity/Message.php`                                 | Entidade   |
| `src/Entity/User.php`                                    | Entidade   |
| `src/Service/MessageService.php`                         | Serviço    |
| `src/Controller/Admin/MessageController.php`             | Controller |
| `src/Repository/MessageRepository.php`                   | Repositório |
| `templates/admin/message/index.html.twig`                | Template   |
| `templates/admin/message/_read_modal.html.twig`          | Template   |
| `templates/admin/message/_widget.html.twig`              | Template   |
| `templates/admin/message/_list_table.html.twig`          | Template   |

---

## 9. Como Replicar em Outro Sistema

### Entidade mínima necessária

```php
// Os campos essenciais para o sistema funcionar:
// sender, recipient, content, sentAt, status, parent, replies
// Opcionais para contexto: subject, contextType, contextId, readAt
```

### Passos para implementar

1. **Criar a entidade** `Message` com auto-relacionamento `parent/replies`.
2. **Criar o serviço** `MessageService` com os métodos `sendMessage()`, `markAsRead()`, `markAsResolved()`, `markAsIgnored()`, `getUnreadCount()`.
3. **Criar o repositório** com `findConversations()` usando a query com `MAX(replies.sentAt)` para ordenação correta.
4. **Criar o controller** com as 6 rotas (widget, index, read, send, reply, status).
5. **Diferenciar Admin de usuário comum** na action `index`: Admin usa `findAllConversations()`, demais usam `findConversations($user)`.
6. **Criar templates**: lista principal e modal de leitura como fragment (carregado via `fetch()`).
7. **Widget no header**: renderizar a rota `/widget` no layout base do admin.

### Ponto de atenção: resolução da thread

O status `resolved` fica na **mensagem raiz**, não nas replies. Ao enviar uma nova reply, o serviço recalcula a raiz e reativa a conversa automaticamente. O filtro padrão da lista **exclui** conversas `resolved`.
