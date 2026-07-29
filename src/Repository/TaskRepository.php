<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }


    public function findByFilters(
        ?string $status,
        ?int $projectId,
        ?int $employeeId
    ): array {

        $qb = $this->createQueryBuilder('t');


        if ($status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }


        if ($projectId) {
            $qb->andWhere('t.project = :project')
                ->setParameter('project', $projectId);
        }


        if ($employeeId) {
            $qb->andWhere('t.employee = :employee')
                ->setParameter('employee', $employeeId);
        }


        return $qb->getQuery()->getResult();
    }
}