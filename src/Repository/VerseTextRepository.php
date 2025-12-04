<?php

namespace App\Repository;

use App\Entity\VerseText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerseText>
 */
class VerseTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerseText::class);
    }
    /**
     * @return VerseText[]
     */
    public function findByVersionAndBook(int $versionId, int $bookId): array
    {
        return $this->createQueryBuilder('vt')
            ->join('vt.verse', 'v')
            ->andWhere('vt.version = :versionId')
            ->andWhere('v.book = :bookId')
            ->setParameter('versionId', $versionId)
            ->setParameter('bookId', $bookId)
            ->getQuery()
            ->getResult();
    }
    /**
     * @return VerseText[]
     */
    public function findVersesByTerm(string $term, int $versionId): array
    {
        return $this->createQueryBuilder('vt')
            ->join('vt.verse', 'v')
            ->join('v.book', 'b')
            ->andWhere('vt.version = :versionId')
            ->andWhere('vt.text LIKE :term')
            ->setParameter('versionId', $versionId)
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('b.testament', 'DESC') // Assuming NT (2) > VT (1) or similar ID structure
            ->addOrderBy('b.bookOrder', 'ASC')
            ->addOrderBy('v.chapter', 'ASC')
            ->addOrderBy('v.verse', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
