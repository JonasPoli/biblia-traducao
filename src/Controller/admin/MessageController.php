<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/admin/message')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('/widget', name: 'app_admin_message_widget', methods: ['GET'])]
    public function widget(MessageService $messageService): Response
    {
        // This renders the notification badge/dropdown in the header
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('');
        }

        return $this->render('admin/message/_widget.html.twig', [
            'unreadCount' => $messageService->getUnreadCount($user),
        ]);
    }

    #[Route('/', name: 'app_admin_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ((int) $user->getWorkGroup() === 0) { // Admin
            $conversations = $messageRepository->findAllConversations();
        } else {
            $conversations = $messageRepository->findConversations($user);
        }

        return $this->render('admin/message/index.html.twig', [
            'conversations' => $conversations,
            'isAdmin' => (int) $user->getWorkGroup() === 0,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_message_read', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function read(Message $message, MessageService $messageService, MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Access check: User must be sender or recipient of the message (or Admin)
        if ($message->getRecipient() !== $user && $message->getSender() !== $user && $user->getWorkGroup() !== 0) {
            throw $this->createAccessDeniedException();
        }

        // Find the root message of the conversation
        $root = $message;
        while ($root->getParent()) {
            $root = $root->getParent();
        }

        // Fetch all messages in this thread (root + descents)
        // Since we don't have a closure table, we might just fetch all messages where parent is in the chain?
        // Simpler: Fetch all where root matches? But we don't store root_id.
        // For now, let's assume 2-level or simple recursion.
        // Or better, let's just fetch ALL messages involving these two users and this context?
        // No, context-based threading is safer.

        // Let's implement a recursive fetch or just use what we have.
        // If the depth is usually small (Message -> Reply -> Reply), we can traverse.
        // But for a linear chat view, we want a flat list.

        // Let's rely on a helper or just fetching all replies recursively.
        $conversation = [$root];
        $this->collectReplies($root, $conversation);

        // Sort by date ASC
        usort($conversation, fn($a, $b) => $a->getSentAt() <=> $b->getSentAt());

        // Mark accessible messages as read
        foreach ($conversation as $msg) {
            if ($msg->getRecipient() === $user && $msg->getStatus() === 'unread') {
                $messageService->markAsRead($msg);
            }
        }

        return $this->render('admin/message/_read_modal.html.twig', [
            'conversation' => $conversation,
            'rootMessage' => $root,
        ]);
    }

    private function collectReplies(Message $message, array &$collection): void
    {
        foreach ($message->getReplies() as $reply) {
            $collection[] = $reply;
            $this->collectReplies($reply, $collection);
        }
    }

    #[Route('/send', name: 'app_admin_message_send', methods: ['POST'])]
    public function send(Request $request, MessageService $messageService, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $recipientId = $data['recipient_id'] ?? null;
        $content = $data['content'] ?? null;
        $subject = $data['subject'] ?? null;
        $contextType = $data['context_type'] ?? null;
        $contextIdInput = $data['context_id'] ?? null;
        $parentId = $data['parent_id'] ?? null;

        $contextId = null;
        if ($contextIdInput !== null) {
            if (is_array($contextIdInput)) {
                $contextId = $contextIdInput;
            } elseif (is_string($contextIdInput)) {
                // Try to decode JSON (e.g. from translation verse)
                $decoded = json_decode($contextIdInput, true);
                if (is_array($decoded)) {
                    $contextId = $decoded;
                } else {
                    // It's likely a simple ID (e.g. from paratext), wrap it
                    $contextId = ['id' => $contextIdInput];
                }
            } elseif (is_int($contextIdInput)) {
                $contextId = ['id' => $contextIdInput];
            }
        }

        if (!$recipientId || !$content) {
            return new JsonResponse(['error' => 'Missing recipient or content'], 400);
        }

        $recipient = $userRepository->find($recipientId);
        if (!$recipient) {
            return new JsonResponse(['error' => 'Recipient not found'], 404);
        }

        // Optional: Check permissions via Voter if needed (e.g., can I message this person?)

        $parent = null;
        /* // Resolving parent if we had Repository injected or via Entity Manager
        if ($parentId) {
             // ...
        } */

        try {
            $message = $messageService->sendMessage($recipient, $content, $subject, $contextType, $contextId, $parent);
            return new JsonResponse(['status' => 'success', 'message_id' => $message->getId()]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/reply', name: 'app_admin_message_reply', methods: ['POST'])]
    public function reply(Message $message, Request $request, MessageService $messageService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($message->getRecipient() !== $user && $user->getWorkGroup() !== 0) {
            // Only recipient (or admin) can reply effectively? 
            // Or maybe sender wants to double reply?
            // Spec says: "Reply" button marks as Responded (if received).
            // Actually, usually you reply to the *Sender* of the message you rely to.
        }

        $content = $request->request->get('content');

        if ($content) {
            $recipient = ($message->getSender() === $user) ? $message->getRecipient() : $message->getSender();

            $messageService->sendMessage(
                $recipient,
                $content,
                null,
                $message->getContextType(),
                $message->getContextId(),
                $message // parent
            );

            // If I am the recipient of the original message, verify if I should mark it as 'answered' logic in Service
        }

        return $this->redirectToRoute('app_admin_message_index');
    }

    #[Route('/{id}/status/{status}', name: 'app_admin_message_status', methods: ['POST'])]
    public function setStatus(Message $message, string $status, MessageService $messageService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($message->getRecipient() !== $user && $user->getWorkGroup() !== 0) {
            throw $this->createAccessDeniedException();
        }

        switch ($status) {
            case 'ignored':
                $messageService->markAsIgnored($message);
                break;
            case 'resolved':
                $messageService->markAsResolved($message);
                break;
        }

        return new JsonResponse(['status' => 'success']);
    }
}
