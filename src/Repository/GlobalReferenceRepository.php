<?php

namespace App\Repository;

use App\Entity\GlobalReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GlobalReference>
 */
class GlobalReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalReference::class);
    }
    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function search(?string $query, int $page = 1, int $limit = 50)
    {
        $qb = $this->createQueryBuilder('g');

        if ($query) {
            $qb->andWhere('g.term LIKE :query OR g.referenceText LIKE :query OR g.foreignWord LIKE :query OR g.strongId LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $qb->orderBy('g.term', 'ASC');

        $query = $qb->getQuery();

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }
}
