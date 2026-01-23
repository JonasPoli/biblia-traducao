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
    public function findConversations(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('r')
            ->leftJoin('m.replies', 'r')
            ->where('m.parent IS NULL')
            ->andWhere('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $user)
            ->groupBy('m.id')
            // Sort by the latest date between root sentAt and max reply sentAt
            ->orderBy('CASE WHEN MAX(r.sentAt) IS NULL THEN m.sentAt ELSE MAX(r.sentAt) END', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Message[] Admin view of all conversations
     */
    public function findAllConversations(): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('r')
            ->leftJoin('m.replies', 'r')
            ->where('m.parent IS NULL')
            ->groupBy('m.id')
            ->orderBy('CASE WHEN MAX(r.sentAt) IS NULL THEN m.sentAt ELSE MAX(r.sentAt) END', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
