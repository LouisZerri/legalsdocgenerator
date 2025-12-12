<?php

namespace App\Repository;

use App\Entity\Template;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Template>
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function findAccessibleByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.visibility = :global')
            ->setParameter('global', 'global');

        if ($user->getOrganization()) {
            $qb->orWhere('t.organization = :org')
                ->setParameter('org', $user->getOrganization());
        }

        return $qb->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, User $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.name LIKE :query OR t.description LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if (!in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $qb->andWhere('t.visibility = :global OR t.organization = :org')
                ->setParameter('global', 'global')
                ->setParameter('org', $user->getOrganization());
        }

        return $qb->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Template[] Returns an array of Template objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Template
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
