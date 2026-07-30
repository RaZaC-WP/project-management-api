<?php

namespace App\Controller\Api;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\OpenApi\EmployeeListResponse;
use App\OpenApi\EmployeeCreateRequest;
use App\OpenApi\EmployeeByIdResponse;
use App\OpenApi\EmployeeUpdateRequest;

#[Route('/api/employees')]
#[OA\Tag(name: 'Employees')]
final class EmployeeController extends AbstractController
{

    #[OA\Get(
        summary: 'List employees',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of employees',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: EmployeeListResponse::class)
                    )
                )
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(
        EmployeeRepository $employeeRepository
    ): JsonResponse {

        $employees = $employeeRepository->findAll();

        return $this->json(
            array_map(
                fn(Employee $employee) => $this->employeeToArray($employee),
                $employees
            )
        );
    }

    #[OA\Post(
        summary: 'Create employee',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: EmployeeCreateRequest::class)
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Employee created',
                content: new OA\JsonContent(
                    ref: new Model(type: EmployeeByIdResponse::class)
                )
            )
        ]
    )]
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

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'Invalid email format'
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

    #[OA\Get(
        summary: 'Get employee by id',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Employee found',
                content: new OA\JsonContent(
                    ref: new Model(type: EmployeeByIdResponse::class)
                )
            )
        ]
    )]
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

    #[OA\Put(
        summary: 'Update employee',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: EmployeeUpdateRequest::class)
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Employee updated',
                content: new OA\JsonContent(
                    ref: new Model(type: EmployeeByIdResponse::class)
                )
            )
        ]
    )]
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

        if (isset($data['fullName'])) {
            $employee->setFullName(
                $data['fullName']
            );
        }

        if (isset($data['email'])) {

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
            $employee->setPosition(
                $data['position']
            );
        }

        $entityManager->flush();

        return $this->json(
            $this->employeeToArray($employee)
        );
    }

    #[OA\Delete(
        summary: 'Delete employee',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Employee deleted'
            )
        ]
    )]
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

        $name = $employee->getFullName();
        $entityManager->remove($employee);
        $entityManager->flush();

        return $this->json([
            'message' => "Employee '{$name}' deleted"
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