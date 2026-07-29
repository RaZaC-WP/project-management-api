<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/employees')]
final class EmployeeController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        EmployeeRepository $employeeRepository
    ): JsonResponse {

        $employees = $employeeRepository->findAll();

        return $this->json(
            array_map(
                fn($employee) => $this->employeeToArray($employee),
                $employees
            )
        );
    }


    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $data = json_decode(
            $request->getContent(),
            true
        );


        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }


        if (
            !isset(
                $data['fullName'],
                $data['email'],
                $data['position']
            )
        ) {
            return $this->json([
                'error' => 'FullName, email and position are required'
            ], 400);
        }


        if (!is_string($data['fullName'])) {
            return $this->json([
                'error' => 'FullName must be a string'
            ], 400);
        }


        if (!is_string($data['email'])) {
            return $this->json([
                'error' => 'Email must be a string'
            ], 400);
        }


        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Invalid email format'
            ], 400);
        }


        if (!is_string($data['position'])) {
            return $this->json([
                'error' => 'Position must be a string'
            ], 400);
        }


        $employee = new Employee();


        $employee->setFullName($data['fullName']);
        $employee->setEmail($data['email']);
        $employee->setPosition($data['position']);


        $entityManager->persist($employee);
        $entityManager->flush();


        return $this->json(
            $this->employeeToArray($employee),
            201
        );
    }


    #[Route('/{id}', methods: ['GET'])]
    public function show(
        int $id,
        EmployeeRepository $employeeRepository
    ): JsonResponse {

        $employee = $employeeRepository->find($id);


        if (!$employee) {
            return $this->json([
                'error' => 'Employee not found'
            ], 404);
        }


        return $this->json(
            $this->employeeToArray($employee)
        );
    }


    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {


        $employee = $employeeRepository->find($id);


        if (!$employee) {
            return $this->json([
                'error' => 'Employee not found'
            ], 404);
        }


        $data = json_decode(
            $request->getContent(),
            true
        );


        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }


        if (isset($data['fullName'])) {

            if (!is_string($data['fullName'])) {
                return $this->json([
                    'error' => 'FullName must be a string'
                ], 400);
            }


            $employee->setFullName(
                $data['fullName']
            );
        }


        if (isset($data['email'])) {

            if (!is_string($data['email'])) {
                return $this->json([
                    'error' => 'Email must be a string'
                ], 400);
            }


            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'error' => 'Invalid email format'
                ], 400);
            }


            $employee->setEmail(
                $data['email']
            );
        }


        if (isset($data['position'])) {

            if (!is_string($data['position'])) {
                return $this->json([
                    'error' => 'Position must be a string'
                ], 400);
            }


            $employee->setPosition(
                $data['position']
            );
        }


        $entityManager->flush();


        return $this->json(
            $this->employeeToArray($employee)
        );
    }


    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $employee = $employeeRepository->find($id);


        if (!$employee) {
            return $this->json([
                'error' => 'Employee not found'
            ], 404);
        }


        $employeeName = $employee->getFullName();


        $entityManager->remove($employee);
        $entityManager->flush();


        return $this->json([
            'message' => "Employee '{$employeeName}' deleted"
        ]);
    }


    private function employeeToArray(Employee $employee): array
    {
        return [
            'id' => $employee->getId(),
            'fullName' => $employee->getFullName(),
            'email' => $employee->getEmail(),
            'position' => $employee->getPosition(),
        ];
    }
}