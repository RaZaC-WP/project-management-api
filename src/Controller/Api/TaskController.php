<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\EmployeeRepository;
use App\OpenApi\TaskListResponse;
use App\OpenApi\TaskCreateRequest;
use App\OpenApi\TaskByIdResponse;
use App\OpenApi\TaskUpdateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/tasks')]
#[OA\Tag(name: 'Tasks')]
final class TaskController extends AbstractApiController
{

    #[OA\Get(
        summary: 'Returns all tasks or filters them by status, project or employee.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by task status (PENDING, IN_PROGRESS, DONE)',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['PENDING', 'IN_PROGRESS', 'DONE']
                )
            ),
            new OA\Parameter(
                name: 'project',
                in: 'query',
                description: 'Filter by project id',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
            new OA\Parameter(
                name: 'employee',
                in: 'query',
                description: 'Filter by employee id',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 2
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of tasks',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: TaskListResponse::class)
                    )
                )
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        TaskRepository $taskRepository
    ): JsonResponse {

        $status = $request->query->get('status');

        if ($status) {

            $status = strtoupper($status);

            if (!$this->isValidStatus($status)) {
                return $this->json([
                    'error' => 'Invalid status'
                ], 400);
            }
        }

        $projectId = $request->query->get('project');
        $employeeId = $request->query->get('employee');

        if ($projectId !== null && !ctype_digit((string) $projectId)) {
            return $this->json([
                'error' => 'project must be a valid id'
            ], 400);
        }

        if ($employeeId !== null && !ctype_digit((string) $employeeId)) {
            return $this->json([
                'error' => 'employee must be a valid id'
            ], 400);
        }

        $tasks = $taskRepository->findByFilters(
            $status,
            $projectId !== null ? (int) $projectId : null,
            $employeeId !== null ? (int) $employeeId : null
        );

        return $this->json(
            array_map(
                fn(Task $task) => $this->taskToArray($task),
                $tasks
            )
        );
    }

    #[OA\Post(
        summary: 'Create task',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: TaskCreateRequest::class)
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created',
                content: new OA\JsonContent(
                    ref: new Model(type: TaskByIdResponse::class)
                )
            )
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ProjectRepository $projectRepository,
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $entityManager
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

        if (!isset($data['title'], $data['projectId'])) {
            return $this->json([
                'error' => 'Title and projectId are required'
            ], 400);
        }

        $project = $projectRepository->find(
            $data['projectId']
        );

        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
            ], 404);
        }

        $employee = null;

        if (isset($data['employeeId'])) {

            $employee = $employeeRepository->find(
                $data['employeeId']
            );

            if (!$employee) {
                return $this->json([
                    'error' => 'Employee not found'
                ], 404);
            }
        }

        $status = strtoupper(
            $data['status'] ?? TaskStatus::PENDING->value
        );

        if (!$this->isValidStatus($status)) {
            return $this->json([
                'error' => 'Invalid status'
            ], 400);
        }

        $task = new Task();

        $task->setTitle(
            $data['title']
        );

        $task->setDescription(
            $data['description'] ?? null
        );

        $task->setStatus(
            $status
        );

        $task->setProject(
            $project
        );

        $task->setEmployee(
            $employee
        );

        $dueDate = $this->parseDate($data['dueDate'] ?? null, 'dueDate');

        if ($dueDate instanceof JsonResponse) {
            return $dueDate;
        }

        if ($dueDate && $dueDate < $task->getCreatedAt()) {
            return $this->json([
                'error' => 'Due date cannot be before creation date'
            ], 400);
        }

        $task->setDueDate($dueDate);

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(
            $this->taskToArray($task),
            201
        );
    }

    #[OA\Get(
        summary: 'Get task by id',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task found',
                content: new OA\JsonContent(
                    ref: new Model(type: TaskByIdResponse::class)
                )
            )
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(
        int $id,
        TaskRepository $taskRepository
    ): JsonResponse {

        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'error' => 'Task not found'
            ], 404);
        }

        return $this->json(
            $this->taskToArray($task)
        );
    }

    #[OA\Put(
        summary: 'Update task',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                ref: new Model(type: TaskUpdateRequest::class)
            )
        )
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository,
        EmployeeRepository $employeeRepository
    ): JsonResponse {

        $data = $this->getJsonData($request);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'error' => 'Task not found'
            ], 404);
        }

        if (isset($data['title'])) {
            $task->setTitle(
                $data['title']
            );
        }

        if (isset($data['description'])) {
            $task->setDescription(
                $data['description']
            );
        }

        if (isset($data['status'])) {

            $status = strtoupper(
                $data['status']
            );

            if (!$this->isValidStatus($status)) {
                return $this->json([
                    'error' => 'Invalid status'
                ], 400);
            }

            $task->setStatus($status);
        }

        if (array_key_exists('dueDate', $data)) {

            $dueDate = $this->parseDate($data['dueDate'], 'dueDate');

            if ($dueDate instanceof JsonResponse) {
                return $dueDate;
            }

            if ($dueDate && $dueDate < $task->getCreatedAt()) {
                return $this->json([
                    'error' => 'Due date cannot be before creation date'
                ], 400);
            }

            $task->setDueDate($dueDate);
        }

        if (isset($data['projectId'])) {

            $project = $projectRepository->find(
                $data['projectId']
            );

            if (!$project) {
                return $this->json([
                    'error' => 'Project not found'
                ], 404);
            }

            $task->setProject($project);
        }


        if (array_key_exists('employeeId', $data)) {

            if ($data['employeeId'] === null) {

                $task->setEmployee(null);

            } else {

                $employee = $employeeRepository->find(
                    $data['employeeId']
                );

                if (!$employee) {
                    return $this->json([
                        'error' => 'Employee not found'
                    ], 404);
                }

                $task->setEmployee($employee);
            }
        }

        $entityManager->flush();

        return $this->json(
            $this->taskToArray($task)
        );
    }

    #[OA\Delete(
        summary: 'Delete task',
        security: [['Bearer' => []]]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'error' => 'Task not found'
            ], 404);
        }

        $title = $task->getTitle();
        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json([
            'message' => "Task '{$title}' deleted"
        ]);
    }

    private function isValidStatus(string $status): bool
    {
        return in_array(
            $status,
            array_map(
                fn(TaskStatus $status) => $status->value,
                TaskStatus::cases()
            ),
            true
        );
    }

    private function taskToArray(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'createdAt' => $task->getCreatedAt()?->format('Y-m-d'),
            'dueDate' => $task->getDueDate()?->format('Y-m-d'),
            'projectId' => $task->getProject()?->getId(),
            'employeeId' => $task->getEmployee()?->getId()
        ];
    }
}