<?php

namespace App\Repository;

use App\Entity\VerseReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerseReference>
 */
class VerseReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerseReference::class);
    }
    /**
     * @return VerseReference[]
     */
    public function findByBook(int $bookId): array
    {
        return $this->createQueryBuilder('vr')
            ->join('vr.verse', 'v')
            ->andWhere('v.book = :bookId')
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getResult();
    }
}
