<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function findByUser(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.template', 't')
            ->addSelect('t');

        // Super admin voit tout
        if (!in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $qb->where('d.createdBy = :user')
                ->setParameter('user', $user);

            if ($user->getOrganization()) {
                $qb->orWhere('d.organization = :org')
                    ->setParameter('org', $user->getOrganization());
            }
        }

        // Filtre par statut
        if ($status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyStats(User $user): array
    {
        // 6 derniers mois
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $months[$date->format('Y-m')] = [
                'label' => $this->formatMonthLabel($date),
                'count' => 0,
            ];
        }

        $qb = $this->createQueryBuilder('d')
            ->select('d.createdAt')
            ->where('d.createdAt >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'));

        // Filtrer selon l'utilisateur
        if (!in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $qb->andWhere('d.createdBy = :user OR d.organization = :org')
                ->setParameter('user', $user)
                ->setParameter('org', $user->getOrganization());
        }

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $monthKey = $row['createdAt']->format('Y-m');
            if (isset($months[$monthKey])) {
                $months[$monthKey]['count']++;
            }
        }

        return array_values($months);
    }

    private function formatMonthLabel(\DateTime $date): string
    {
        $frenchMonths = [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc'
        ];

        return $frenchMonths[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    public function search(string $query, User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.template', 't')
            ->addSelect('t')
            ->where('t.name LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if (!in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $qb->andWhere('d.createdBy = :user OR d.organization = :org')
                ->setParameter('user', $user)
                ->setParameter('org', $user->getOrganization());
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Document[] Returns an array of Document objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Document
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
