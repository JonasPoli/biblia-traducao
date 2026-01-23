<?php

namespace App\Repository;

use App\Entity\LouwNida;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LouwNida>
 *
 * @method LouwNida|null find($id, $lockMode = null, $lockVersion = null)
 * @method LouwNida|null findOneBy(array $criteria, array $orderBy = null)
 * @method LouwNida[]    findAll()
 * @method LouwNida[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LouwNidaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LouwNida::class);
    }

    /**
     * Find occurrences by LN number (e.g., "93.169" or "LN-93.169")
     * Uses LIKE to match variations in storage format
     * 
     * @return LouwNida[]
     */
    public function findByLnNumber(string $lnNumber): array
    {
        // Normalize the LN number - remove "LN-" prefix if present
        $normalized = str_replace('LN-', '', $lnNumber);

        return $this->createQueryBuilder('l')
            ->where('l.lnNumber LIKE :ln1')
            ->orWhere('l.lnNumber LIKE :ln2')
            ->orWhere('l.lnNumber = :ln3')
            ->orWhere('l.lnNumber = :ln4')
            ->setParameter('ln1', '%' . $normalized . '%')
            ->setParameter('ln2', '%LN-' . $normalized . '%')
            ->setParameter('ln3', $normalized)
            ->setParameter('ln4', 'LN-' . $normalized)
            ->orderBy('l.book', 'ASC')
            ->addOrderBy('l.chapter', 'ASC')
            ->addOrderBy('l.verse', 'ASC')
            ->addOrderBy('l.ogntSort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all words in a specific verse ordered by ogntSort
     * 
     * @return LouwNida[]
     */
    public function findVerseWords(int $book, int $chapter, int $verse): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.book = :book')
            ->andWhere('l.chapter = :chapter')
            ->andWhere('l.verse = :verse')
            ->setParameter('book', $book)
            ->setParameter('chapter', $chapter)
            ->setParameter('verse', $verse)
            ->orderBy('l.ogntSort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unique verse references for a given LN number
     * Returns deduplicated book/chapter/verse combinations
     * 
     * @return array<array{book: int, chapter: int, verse: int}>
     */
    public function findUniqueVersesByLnNumber(string $lnNumber): array
    {
        $normalized = str_replace('LN-', '', $lnNumber);

        return $this->createQueryBuilder('l')
            ->select('DISTINCT l.book, l.chapter, l.verse')
            ->where('l.lnNumber LIKE :ln1')
            ->orWhere('l.lnNumber LIKE :ln2')
            ->orWhere('l.lnNumber = :ln3')
            ->orWhere('l.lnNumber = :ln4')
            ->setParameter('ln1', '%' . $normalized . '%')
            ->setParameter('ln2', '%LN-' . $normalized . '%')
            ->setParameter('ln3', $normalized)
            ->setParameter('ln4', 'LN-' . $normalized)
            ->orderBy('l.book', 'ASC')
            ->addOrderBy('l.chapter', 'ASC')
            ->addOrderBy('l.verse', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
//     * @return LouwNida[] Returns an array of LouwNida objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?LouwNida
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
