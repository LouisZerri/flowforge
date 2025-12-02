<?php

namespace App\Repository;

use App\Entity\WorkflowSubject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkflowSubject>
 */
class WorkflowSubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowSubject::class);
    }

    /**
     * @return WorkflowSubject[]
     */
    public function findOverdueSubjects(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.deadline IS NOT NULL')
            ->andWhere('s.deadline < :now')
            ->andWhere('s.currentPlace NOT IN (:finalPlaces)')
            ->setParameter('now', $now)
            ->setParameter('finalPlaces', ['livree', 'ferme', 'publie', 'archive', 'annulee', 'remboursee', 'approuvee', 'refusee'])
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return WorkflowSubject[] Returns an array of WorkflowSubject objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WorkflowSubject
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
