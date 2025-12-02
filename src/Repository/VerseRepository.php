<?php

namespace App\Repository;

use App\Entity\Verse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Verse>
 */
class VerseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Verse::class);
    }

    /**
     * @return Verse[]
     */
    public function getVersesForTranslation(int $bookId, int $chapter, array $versionIds): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.verseTexts', 'vt')
            ->addSelect('vt')
            ->where('v.book = :bookId')
            ->andWhere('v.chapter = :chapter')
            ->andWhere('vt.version IN (:versionIds) OR vt.version IS NULL')
            ->setParameter('bookId', $bookId)
            ->setParameter('chapter', $chapter)
            ->setParameter('versionIds', $versionIds)
            ->orderBy('v.verse', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
