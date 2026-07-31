<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Enum\TaskStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Project;
use App\Entity\Employee;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{

    public const TASK_1_REFERENCE = 'task_1';
    public const TASK_2_REFERENCE = 'task_2';
    public const TASK_3_REFERENCE = 'task_3';

    public function load(ObjectManager $manager): void
    {

        $project1 = $this->getReference(
            ProjectFixtures::PROJECT_1_REFERENCE,
            Project::class
        );

        $project2 = $this->getReference(
            ProjectFixtures::PROJECT_2_REFERENCE,
            Project::class
        );

        $employee1 = $this->getReference(
            EmployeeFixtures::EMPLOYEE_1_REFERENCE,
            Employee::class
        );


        $employee2 = $this->getReference(
            EmployeeFixtures::EMPLOYEE_2_REFERENCE,
            Employee::class
        );

        $task1 = new Task();

        $task1
            ->setTitle('Implement JWT authentication')
            ->setDescription('Create authentication system using JWT tokens')
            ->setStatus(TaskStatus::DONE->value)
            ->setProject($project1)
            ->setEmployee($employee1)
            ->setDueDate(
                new \DateTimeImmutable('2026-09-30')
            );

        $task2 = new Task();

        $task2
            ->setTitle('Create REST endpoints')
            ->setDescription('Develop CRUD endpoints for projects, employees and tasks')
            ->setStatus(TaskStatus::IN_PROGRESS->value)
            ->setProject($project1)
            ->setEmployee($employee2)
            ->setDueDate(
                new \DateTimeImmutable('2026-10-15')
            );

        $task3 = new Task();

        $task3
            ->setTitle('Moodle database migration')
            ->setDescription('Migrate existing Moodle data')
            ->setStatus(TaskStatus::PENDING->value)
            ->setProject($project2)
            ->setDueDate(
                new \DateTimeImmutable('2026-11-20')
            );

        $manager->persist($task1);
        $manager->persist($task2);
        $manager->persist($task3);

        $manager->flush();

        $this->addReference(
            self::TASK_1_REFERENCE,
            $task1
        );

        $this->addReference(
            self::TASK_2_REFERENCE,
            $task2
        );

        $this->addReference(
            self::TASK_3_REFERENCE,
            $task3
        );
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            EmployeeFixtures::class,
        ];
    }
}