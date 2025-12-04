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
}
