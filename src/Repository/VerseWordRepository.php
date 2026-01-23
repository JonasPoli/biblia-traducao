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

    /**
     * Find all words in a specific verse by book ID, chapter, and verse number
     * 
     * @return VerseWord[]
     */
    public function findByBookChapterVerse(int $bookId, int $chapter, int $verse): array
    {
        return $this->createQueryBuilder('vw')
            ->join('vw.verse', 'v')
            ->join('v.book', 'b')
            ->where('b.id = :bookId')
            ->andWhere('v.chapter = :chapter')
            ->andWhere('v.verse = :verse')
            ->setParameter('bookId', $bookId)
            ->setParameter('chapter', $chapter)
            ->setParameter('verse', $verse)
            ->orderBy('vw.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
