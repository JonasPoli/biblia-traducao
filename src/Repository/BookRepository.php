<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return array<array{id: int, name: string, abbreviation: string, total: int, translated: int}>
     */
    public function getBooksWithProgress(int $targetVersionId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                b.id, 
                b.name, 
                b.abbreviation,
                COUNT(DISTINCT v.id) as total,
                COUNT(DISTINCT vt.id) as translated
            FROM book b
            JOIN verse v ON v.book_id = b.id
            LEFT JOIN verse_text vt ON vt.verse_id = v.id AND vt.version_id = :versionId
            GROUP BY b.id, b.name, b.abbreviation, b.book_order
            ORDER BY b.book_order ASC
        ';

        $resultSet = $conn->executeQuery($sql, ['versionId' => $targetVersionId]);

        return $resultSet->fetchAllAssociative();
    }

    /**
     * @return array<array{chapter: int, total: int, translated: int}>
     */
    public function getChaptersWithProgress(int $bookId, int $targetVersionId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                v.chapter,
                COUNT(DISTINCT v.id) as total,
                COUNT(DISTINCT vt.id) as translated
            FROM verse v
            LEFT JOIN verse_text vt ON vt.verse_id = v.id AND vt.version_id = :versionId
            WHERE v.book_id = :bookId
            GROUP BY v.chapter
            ORDER BY v.chapter ASC
        ';

        $resultSet = $conn->executeQuery($sql, [
            'bookId' => $bookId,
            'versionId' => $targetVersionId
        ]);

        return $resultSet->fetchAllAssociative();
    }
    /**
     * @return array{total: int, translated: int}
     */
    public function getGlobalProgress(int $targetVersionId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT 
                COUNT(DISTINCT v.id) as total,
                COUNT(DISTINCT vt.id) as translated
            FROM verse v
            LEFT JOIN verse_text vt ON vt.verse_id = v.id AND vt.version_id = :versionId
        ';

        $resultSet = $conn->executeQuery($sql, ['versionId' => $targetVersionId]);

        return $resultSet->fetchAssociative();
    }
}
