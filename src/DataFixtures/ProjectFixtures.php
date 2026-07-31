<?php

namespace App\DataFixtures;

use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Employee;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROJECT_1_REFERENCE = 'project_1';
    public const PROJECT_2_REFERENCE = 'project_2';

    public function load(ObjectManager $manager): void
    {
        $employee1 = $this->getReference(
            EmployeeFixtures::EMPLOYEE_1_REFERENCE,
            Employee::class
        );

        $employee2 = $this->getReference(
            EmployeeFixtures::EMPLOYEE_2_REFERENCE,
            Employee::class
        );

        $project1 = new Project();

        $project1
            ->setName('Project Management API')
            ->setDescription('Backend API developed with Symfony 7.4')
            ->setStartDate(new \DateTimeImmutable('2026-08-01'))
            ->setEndDate(new \DateTimeImmutable('2026-12-31'));

        $project1->addEmployee($employee1);
        $project1->addEmployee($employee2);

        $project2 = new Project();

        $project2
            ->setName('Moodle Migration')
            ->setDescription('Migration and upgrade of Moodle platform')
            ->setStartDate(new \DateTimeImmutable('2026-09-01'))
            ->setEndDate(new \DateTimeImmutable('2027-01-15'));

        $project2->addEmployee($employee1);

        $manager->persist($project1);
        $manager->persist($project2);

        $manager->flush();

        $this->addReference(
            self::PROJECT_1_REFERENCE,
            $project1
        );

        $this->addReference(
            self::PROJECT_2_REFERENCE,
            $project2
        );
    }

    public function getDependencies(): array
    {
        return [
            EmployeeFixtures::class,
        ];
    }
}