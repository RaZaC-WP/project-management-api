<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\OpenApi\ProjectListResponse;
use App\OpenApi\ProjectCreateRequest;
use App\OpenApi\ProjectByIdResponse;
use App\OpenApi\ProjectUpdateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Repository\EmployeeRepository;
use App\Repository\TaskRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

#[Route('/api/projects')]
#[OA\Tag(name: 'Projects')]
final class ProjectController extends AbstractApiController
{

    #[OA\Get(
        summary: 'List projects',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of projects',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: ProjectListResponse::class)
                    )
                )
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(
        ProjectRepository $projectRepository
    ): JsonResponse {

        $projects = $projectRepository->findAll();

        return $this->json(
            array_map(
                fn(Project $project) => $this->projectToArray($project),
                $projects
            )
        );
    }

    #[OA\Post(
        summary: 'Create project',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: ProjectCreateRequest::class)
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created',
                content: new OA\JsonContent(
                    ref: new Model(type: ProjectByIdResponse::class)
                )
            )
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        EmployeeRepository $employeeRepository,
        TaskRepository $taskRepository
    ): JsonResponse {

        $data = $this->getJsonData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        if (
            !isset(
            $data['name'],
            $data['startDate']
        )
        ) {
            return $this->json([
                'error' => 'Name and startDate are required'
            ], 400);
        }

        if (!is_string($data['name'])) {
            return $this->json([
                'error' => 'Name must be a string'
            ], 400);
        }

        $startDate = $this->parseDate($data['startDate'], 'startDate');

        if ($startDate instanceof JsonResponse) {
            return $startDate;
        }

        $endDate = $this->parseDate($data['endDate'] ?? null, 'endDate');

        if ($endDate instanceof JsonResponse) {
            return $endDate;
        }

        if ($endDate && $endDate < $startDate) {
            return $this->json([
                'error' => 'End date cannot be before start date'
            ], 400);
        }

        $project = new Project();

        $project->setName($data['name']);

        $project->setDescription(
            $data['description'] ?? null
        );

        $project->setStartDate($startDate);
        $project->setEndDate($endDate);
        try {

            $this->updateRelations(
                $project,
                $data,
                $employeeRepository,
                $taskRepository
            );

        } catch (\RuntimeException $e) {

            return $this->json([
                'error' => $e->getMessage()
            ], 404);
        }

        $entityManager->persist($project);
        $entityManager->flush();

        return $this->json(
            $this->projectDetailToArray($project),
            201
        );
    }

    #[OA\Get(
        summary: 'Get project by id',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project found',
                content: new OA\JsonContent(
                    ref: new Model(type: ProjectByIdResponse::class)
                )
            )
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(
        int $id,
        ProjectRepository $projectRepository
    ): JsonResponse {

        $project = $projectRepository->find($id);

        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
            ], 404);
        }

        return $this->json(
            $this->projectDetailToArray($project)
        );
    }

    #[OA\Put(
        summary: 'Update project',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: ProjectUpdateRequest::class)
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated',
                content: new OA\JsonContent(
                    ref: new Model(type: ProjectByIdResponse::class)
                )
            )
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        EntityManagerInterface $entityManager,
        EmployeeRepository $employeeRepository,
        TaskRepository $taskRepository
    ): JsonResponse {

        $data = $this->getJsonData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $project = $projectRepository->find($id);

        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
            ], 404);
        }

        if (isset($data['name'])) {
            $project->setName($data['name']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['startDate'])) {

            $startDate = $this->parseDate($data['startDate'], 'startDate');

            if ($startDate instanceof JsonResponse) {
                return $startDate;
            }

            $project->setStartDate($startDate);
        }

        if (array_key_exists('endDate', $data)) {

            $endDate = $this->parseDate($data['endDate'], 'endDate');

            if ($endDate instanceof JsonResponse) {
                return $endDate;
            }

            $project->setEndDate($endDate);
        }

        if ($project->getEndDate() && $project->getEndDate() < $project->getStartDate()) {
            return $this->json([
                'error' => 'End date cannot be before start date'
            ], 400);
        }

        try {

            $this->updateRelations(
                $project,
                $data,
                $employeeRepository,
                $taskRepository
            );

        } catch (\RuntimeException $e) {

            return $this->json([
                'error' => $e->getMessage()
            ], 404);
        }

        $entityManager->flush();

        return $this->json(
            $this->projectDetailToArray($project)
        );
    }

    #[OA\Delete(
        summary: 'Delete project',
        security: [['Bearer' => []]]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        ProjectRepository $projectRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $project = $projectRepository->find($id);

        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
            ], 404);
        }

        $name = $project->getName();

        if ($project->getTasks()->count() > 0) {
            return $this->json([
                'error' => "Project '{$name}' cannot be deleted because it has associated tasks."
            ], 409);
        }

        try {

            foreach ($project->getEmployees() as $employee) {
                $project->removeEmployee($employee);
            }

            $entityManager->remove($project);
            $entityManager->flush();

        } catch (ForeignKeyConstraintViolationException $e) {

            return $this->json([
                'error' => "Project '{$name}' cannot be deleted."
            ], 409);
        }

        return $this->json([
            'message' => "Project '{$name}' deleted"
        ]);
    }

    private function projectToArray(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d')
        ];
    }

    private function projectDetailToArray(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),

            'employees' => array_map(
                fn($employee) => [
                    'id' => $employee->getId(),
                    'fullName' => $employee->getFullName(),
                    'email' => $employee->getEmail(),
                    'position' => $employee->getPosition()
                ],
                $project->getEmployees()->toArray()
            ),

            'tasks' => array_map(
                fn($task) => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus()
                ],
                $project->getTasks()->toArray()
            )
        ];
    }
    private function updateRelations(
        Project $project,
        array $data,
        EmployeeRepository $employeeRepository,
        TaskRepository $taskRepository
    ): void {

        if (isset($data['employeeIds'])) {

            foreach ($project->getEmployees() as $employee) {
                $project->removeEmployee($employee);
            }

            foreach ($data['employeeIds'] as $employeeId) {

                $employee = $employeeRepository->find($employeeId);

                if (!$employee) {
                    throw new \RuntimeException(
                        "Employee with id {$employeeId} not found"
                    );
                }

                $project->addEmployee($employee);
            }
        }


        if (isset($data['taskIds'])) {

            foreach ($project->getTasks() as $task) {
                $project->removeTask($task);
            }

            foreach ($data['taskIds'] as $taskId) {

                $task = $taskRepository->find($taskId);

                if (!$task) {
                    throw new \RuntimeException(
                        "Task with id {$taskId} not found"
                    );
                }

                $project->addTask($task);
            }
        }
    }
}