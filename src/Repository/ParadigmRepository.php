<?php

namespace App\Repository;

use App\Entity\Paradigm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paradigm>
 *
 * @method Paradigm|null find($id, $lockMode = null, $lockVersion = null)
 * @method Paradigm|null findOneBy(array $criteria, array $orderBy = null)
 * @method Paradigm[]    findAll()
 * @method Paradigm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParadigmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paradigm::class);
    }

    public function save(Paradigm $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Paradigm $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
