<?php

namespace App\Repository;

use App\Entity\VerseWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerseWord>
 */
class VerseWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerseWord::class);
    }
    /**
     * @return VerseWord[]
     */
    public function findOccurrences(\App\Entity\StrongDefinition $strongCode, int $limit = 20): array
    {
        return $this->createQueryBuilder('vw')
            ->join('vw.verse', 'v')
            ->join('v.book', 'b')
            ->where('vw.strongDefinition = :strongCode')
            ->setParameter('strongCode', $strongCode)
            ->orderBy('b.bookOrder', 'ASC')
            ->addOrderBy('v.chapter', 'ASC')
            ->addOrderBy('v.verse', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
