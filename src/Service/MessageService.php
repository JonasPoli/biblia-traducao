<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MessageService
{
    public function __construct(
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function sendMessage(User $recipient, string $content, ?string $subject = null, ?string $contextType = null, ?array $contextId = null, ?Message $parent = null): Message
    {
        $sender = $this->security->getUser();
        if (!$sender instanceof User) {
            throw new \LogicException('User must be logged in to send a message.');
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);
        $message->setSubject($subject);
        $message->setContextType($contextType);
        $message->setContextId($contextId);
        $message->setParent($parent);

        if ($parent) {
            $message->setSubject('Re: ' . ($parent->getSubject() ?? 'Sem assunto'));
            // Reset status of the thread (Root) if it was resolved
            $root = $parent;
            while ($root->getParent()) {
                $root = $root->getParent();
            }
            // If the thread was resolved, or simply to indicate new activity
            if ($root->getStatus() === 'resolved' || $root->getStatus() === 'read' || $root->getStatus() === 'unread') {
                $root->setStatus('replied'); // Or specific status like 'active'? 'replied' is fine.
            }
            // Also mark immediate parent as replied?
            $parent->setStatus('replied');
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function markAsRead(Message $message): void
    {
        if ($message->getReadAt() !== null) {
            return;
        }

        $message->setReadAt(new \DateTimeImmutable());
        if ($message->getStatus() === 'unread') {
            $message->setStatus('read');
        }

        $this->entityManager->flush();
    }

    public function markAsIgnored(Message $message): void
    {
        $message->setStatus('ignored');
        $this->entityManager->flush();
    }

    public function markAsResolved(Message $message): void
    {
        $message->setStatus('resolved');
        $this->entityManager->flush();
    }

    public function getUnreadCount(User $user): int
    {
        return count($this->messageRepository->findUnreadByUser($user));
    }
}
