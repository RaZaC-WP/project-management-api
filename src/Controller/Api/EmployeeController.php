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

    private function employeeToArray(Employee $employee): array
    {
        return [
            'id' => $employee->getId(),
            'fullName' => $employee->getFullName(),
            'email' => $employee->getEmail(),
            'position' => $employee->getPosition(),
        ];
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

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        if (!isset($data['fullName'], $data['email'], $data['position'])) {
            return $this->json([
                'error' => 'FullName, email and position are required'
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

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        if (isset($data['fullName'])) {
            $employee->setFullName($data['fullName']);
        }

        if (isset($data['email'])) {
            $employee->setEmail($data['email']);
        }

        if (isset($data['position'])) {
            $employee->setPosition($data['position']);
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
}