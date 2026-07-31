<?php

namespace App\DataFixtures;

use App\Entity\Employee;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmployeeFixtures extends Fixture
{
    public const EMPLOYEE_1_REFERENCE = 'employee_1';
    public const EMPLOYEE_2_REFERENCE = 'employee_2';
    public const EMPLOYEE_3_REFERENCE = 'employee_3';


    public function load(ObjectManager $manager): void
    {
        $employees = [

            [
                'name' => 'Javier Molinos',
                'email' => 'javi@gmail.com',
                'position' => 'Backend Developer'
            ],

            [
                'name' => 'Laura Pérez',
                'email' => 'laura@gmail.com',
                'position' => 'Frontend Developer'
            ],

            [
                'name' => 'Carlos Gómez',
                'email' => 'carlos@gmail.com',
                'position' => 'QA Tester'
            ]

        ];


        foreach ($employees as $index => $data) {

            $employee = new Employee();

            $employee
                ->setFullName($data['name'])
                ->setEmail($data['email'])
                ->setPosition($data['position']);


            $manager->persist($employee);


            $this->addReference(
                match ($index) {
                    0 => self::EMPLOYEE_1_REFERENCE,
                    1 => self::EMPLOYEE_2_REFERENCE,
                    2 => self::EMPLOYEE_3_REFERENCE,
                },
                $employee
            );
        }


        $manager->flush();
    }
}