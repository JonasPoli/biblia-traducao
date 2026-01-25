<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'unread')
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.recipient = :user OR m.sender = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Message[] Returns an array of Message objects (Roots)
     */
    public function findInboxThreads(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.replies', 'r')
            ->where('m.parent IS NULL')
            ->andWhere('m.recipient = :user OR r.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->distinct();

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Message[] Returns an array of Message objects (Roots)
     */
    public function findSentThreads(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.parent IS NULL')
            ->andWhere('m.sender = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Message[] Returns an array of Message (Roots) sorted by last interaction
     */
    public function findConversations(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->addSelect('r')
            ->leftJoin('m.replies', 'r')
            ->where('m.parent IS NULL')
            ->andWhere('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $user)
            ->groupBy('m.id')
            ->orderBy('CASE WHEN MAX(r.sentAt) IS NULL THEN m.sentAt ELSE MAX(r.sentAt) END', 'DESC');

        if ($status === 'unread') {
            // Unread logic: Root is unread OR any reply is unread (and recipient is user)
            // This is complex in DQL with join. Simpler to check root status matches 'unread' if recipient is user
            // OR check distinct roots where exists a reply with status unread.
            
            // Simplified: Just check current status of ROOT if it matches logic? 
            // The "Status" field on Message entity (root) often tracks the overall conversation state in some designs,
            // but here it seems per-message. 
            // Re-reading User Request: "Squad appearing in this list messages unread, read, ignored, replied"
            // "When clicking 'Unread' should appear only with selected status"
            
            // If the user wants to filter by "Status" of the conversation, we generally look at the Root Message status
            // OR we calculate it. 
            // In Entity, `status` is on each message. 
            // Let's filter by the ROOT message status for simplicity and performance, 
            // unless 'unread' specifically means "I have unread messages in this thread".
            
            // Given the requested list: unread, read, ignored, replied, resolved.
            // These map directly to the `status` column on the message table.
            // So we will filter `m.status`.
            $qb->andWhere('m.status = :status')
               ->setParameter('status', 'unread');
        } elseif ($status === 'all') {
            // No filter
        } elseif ($status) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        } else {
            // Default: Show unread, read, ignored, replied (NOT resolved)
            $qb->andWhere('m.status IN (:statuses)')
               ->setParameter('statuses', ['unread', 'read', 'ignored', 'replied']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Message[] Admin view of all conversations
     */
    public function findAllConversations(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->addSelect('r')
            ->leftJoin('m.replies', 'r')
            ->where('m.parent IS NULL')
            ->groupBy('m.id')
            ->orderBy('CASE WHEN MAX(r.sentAt) IS NULL THEN m.sentAt ELSE MAX(r.sentAt) END', 'DESC');

        if ($status === 'all') {
            // No filter
        } elseif ($status) {
             $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        } else {
            // Default: Show unread, read, ignored, replied (NOT resolved)
            $qb->andWhere('m.status IN (:statuses)')
               ->setParameter('statuses', ['unread', 'read', 'ignored', 'replied']);
        }

        return $qb->getQuery()->getResult();
    }
}
