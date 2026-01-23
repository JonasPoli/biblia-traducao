<?php

namespace App\Repository;

use App\Entity\LouwNidaDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LouwNidaDomain>
 *
 * @method LouwNidaDomain|null find($id, $lockMode = null, $lockVersion = null)
 * @method LouwNidaDomain|null findOneBy(array $criteria, array $orderBy = null)
 * @method LouwNidaDomain[]    findAll()
 * @method LouwNidaDomain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LouwNidaDomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LouwNidaDomain::class);
    }

    /**
     * Find all subdomains belonging to a domain number
     * 
     * @return LouwNidaDomain[]
     */
    public function findSubdomainsByDomain(int $domainNumber): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.domainNumber = :domainNumber')
            ->setParameter('domainNumber', $domainNumber)
            ->orderBy('d.subdomainNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific domain by domain and subdomain numbers
     */
    public function findByDomainAndSubdomain(int $domainNumber, int $subdomainNumber): ?LouwNidaDomain
    {
        return $this->createQueryBuilder('d')
            ->where('d.domainNumber = :domainNumber')
            ->andWhere('d.subdomainNumber = :subdomainNumber')
            ->setParameter('domainNumber', $domainNumber)
            ->setParameter('subdomainNumber', $subdomainNumber)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get the category name for a domain (from the first subdomain)
     */
    public function getDomainCategory(int $domainNumber): ?string
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.category')
            ->where('d.domainNumber = :domainNumber')
            ->setParameter('domainNumber', $domainNumber)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['category'] : null;
    }
}
