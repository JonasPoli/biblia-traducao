<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Repository\VerseRepository;
use App\Repository\VerseTextRepository;
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
    public function index(MessageRepository $messageRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->query->get('status');
        // If no status is provided, default to null (repo handles default of unread/read/ignored/replied)

        if ((int) $user->getWorkGroup() === 0) { // Admin
            $conversations = $messageRepository->findAllConversations($status);
        } else {
            $conversations = $messageRepository->findConversations($user, $status);
        }

        return $this->render('admin/message/index.html.twig', [
            'conversations' => $conversations,
            'isAdmin' => (int) $user->getWorkGroup() === 0,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_message_read', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function read(
        Message $message,
        MessageService $messageService,
        MessageRepository $messageRepository,
        BookRepository $bookRepository,
        VerseRepository $verseRepository,
        VerseTextRepository $verseTextRepository
    ): Response {
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

        // Load verse context if available
        $verseContext = null;
        if ($root->getContextType() === 'translation' && $root->getContextId()) {
            $contextId = $root->getContextId();
            if (is_string($contextId)) {
                $contextId = json_decode($contextId, true);
            }

            $bookId = $contextId['book'] ?? $contextId['book_id'] ?? null;
            $chapter = $contextId['chapter'] ?? null;
            $verseNum = $contextId['verse'] ?? $contextId['verse_num'] ?? null;

            if ($bookId && $chapter && $verseNum) {
                $book = is_numeric($bookId)
                    ? $bookRepository->find((int) $bookId)
                    : ($bookRepository->findOneBy(['abbreviation' => $bookId]) ?? $bookRepository->findOneBy(['name' => $bookId]));

                if ($book) {
                    $verse = $verseRepository->findOneBy([
                        'book' => $book,
                        'chapter' => (int) $chapter,
                        'verse' => (int) $verseNum,
                    ]);

                    if ($verse) {
                        $isOldTestament = ($book->getTestament() && $book->getTestament()->getId() === 1);
                        $originalVersionId = $isOldTestament ? 19 : 18; // 19 = Hebrew (HSB), 18 = Greek (BGB)

                        // 1. Original (Greek or Hebrew)
                        $originalVerseText = $verseTextRepository->findOneBy([
                            'verse' => $verse,
                            'version' => $originalVersionId,
                        ]);

                        $originalHtml = '';
                        $verseWords = $verse->getVerseWords();
                        if (count($verseWords) > 0) {
                            foreach ($verseWords as $word) {
                                $strongCode = $word->getStrongCode();
                                $originalWord = $word->getWordOriginal();
                                if ($strongCode) {
                                    $ptType = $word->getPortugueseType();
                                    $span = "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 dark:hover:bg-amber-600/60 dark:hover:text-amber-100 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\" data-original=\"{$originalWord}\">{$originalWord}</span>";
                                    if ($ptType) {
                                        $originalHtml .= "<sl-tooltip content=\"{$ptType}\">{$span}</sl-tooltip> ";
                                    } else {
                                        $originalHtml .= "{$span} ";
                                    }
                                } else {
                                    $originalHtml .= "{$originalWord} ";
                                }
                            }
                            $originalHtml = trim($originalHtml);
                        }

                        if (!$originalHtml && $originalVerseText) {
                            $originalHtml = $originalVerseText->getText();
                        }

                        // 2. Almeida (Strong) (Version 22)
                        $almeidaHtml = '';
                        $verseText22 = $verseTextRepository->findOneBy([
                            'verse' => $verse,
                            'version' => 22,
                        ]);

                        if ($verseText22) {
                            $text22 = $verseText22->getText();
                            $referenceHtml = '';
                            preg_match_all('/(?P<translation>[^<>]+)<S>(?P<strongCode>[HG]\d+)<\/S>\s*<n>(?P<original>[^<]+)<\/n>/u', $text22, $matchesHtml, PREG_SET_ORDER);

                            foreach ($matchesHtml as $match) {
                                $strongCode = $match['strongCode'];
                                $translationWord = trim($match['translation']);

                                $translationWordClean = preg_replace('/[.,!?:;()"\'-]+/', ' ', $translationWord);
                                $translationWordClean = str_replace(['/S>', '<S>', '</S>', 'pb/>', 'pb/'], '', $translationWordClean);
                                $translationWordClean = trim($translationWordClean);

                                $referenceHtml .= "<span class=\"strong-word cursor-pointer hover:bg-yellow-200 dark:hover:bg-amber-600/60 dark:hover:text-amber-100 transition-colors rounded px-0.5\" data-strong=\"{$strongCode}\">{$translationWordClean}</span> ";
                            }
                            $almeidaHtml = trim($referenceHtml);
                        }

                        // Fallback to Version 1 if Version 22 is missing
                        if (!$almeidaHtml) {
                            $verseText1 = $verseTextRepository->findOneBy([
                                'verse' => $verse,
                                'version' => 1,
                            ]);
                            if ($verseText1) {
                                $almeidaHtml = $verseText1->getText();
                            }
                        }

                        $verseContext = [
                            'book' => $book,
                            'chapter' => (int) $chapter,
                            'verseNum' => (int) $verseNum,
                            'originalLanguage' => $isOldTestament ? 'Hebraico' : 'Grego',
                            'originalHtml' => $originalHtml,
                            'almeidaHtml' => $almeidaHtml,
                        ];
                    }
                }
            }
        }

        return $this->render('admin/message/_read_modal.html.twig', [
            'conversation' => $conversation,
            'rootMessage' => $root,
            'verseContext' => $verseContext,
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
