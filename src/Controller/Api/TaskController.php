<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/tasks')]
final class TaskController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        TaskRepository $taskRepository
    ): JsonResponse {

        $status = $request->query->get('status');

        if ($status !== null) {

            $status = strtoupper($status);

            if (!$this->isValidStatus($status)) {
                return $this->json([
                    'error' => 'Invalid status filter',
                    'allowedStatuses' => array_map(
                        fn(TaskStatus $status) => $status->value,
                        TaskStatus::cases()
                    )
                ], 400);
            }
        }


        $tasks = $taskRepository->findByFilters(
            $status,
            $request->query->get('project')
                ? (int) $request->query->get('project')
                : null,
            $request->query->get('employee')
                ? (int) $request->query->get('employee')
                : null
        );

        return $this->json(
            array_map(
                fn($task) => $this->taskToArray($task),
                $tasks
            )
        );
    }


    #[OA\Post(
        path: '/api/tasks',
        summary: 'Create a new task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/Task'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Task'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid data'
            ),
            new OA\Response(
                response: 404,
                description: 'Project or employee not found'
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

        $data = json_decode(
            $request->getContent(),
            true
        );

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


        if (!is_string($data['title'])) {
            return $this->json([
                'error' => 'Title must be a string'
            ], 400);
        }


        if (
            isset($data['description']) &&
            !is_string($data['description'])
        ) {
            return $this->json([
                'error' => 'Description must be a string'
            ], 400);
        }


        if (!is_int($data['projectId'])) {
            return $this->json([
                'error' => 'projectId must be an integer'
            ], 400);
        }


        $status = strtoupper(
            (string) ($data['status'] ?? TaskStatus::PENDING->value)
        );


        if (!$this->isValidStatus($status)) {
            return $this->json([
                'error' => 'Invalid status'
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

            if (!is_int($data['employeeId'])) {
                return $this->json([
                    'error' => 'employeeId must be an integer'
                ], 400);
            }


            $employee = $employeeRepository->find(
                $data['employeeId']
            );


            if (!$employee) {
                return $this->json([
                    'error' => 'Employee not found'
                ], 404);
            }
        }


        $task = new Task();


        $task->setTitle($data['title']);

        $task->setDescription(
            $data['description'] ?? null
        );

        $task->setStatus($status);


        if (isset($data['dueDate'])) {

            $dueDate = \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data['dueDate']
            );


            $errors = \DateTimeImmutable::getLastErrors();


            if (
                !$dueDate ||
                ($errors !== false && $errors['warning_count'] > 0) ||
                ($errors !== false && $errors['error_count'] > 0)
            ) {
                return $this->json([
                    'error' => 'Invalid dueDate format. Expected Y-m-d'
                ], 400);
            }


            $task->setDueDate($dueDate);
        }


        $task->setProject($project);
        $task->setEmployee($employee);


        $entityManager->persist($task);
        $entityManager->flush();


        return $this->json(
            $this->taskToArray($task),
            201
        );
    }


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


    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $task = $taskRepository->find($id);


        if (!$task) {
            return $this->json([
                'error' => 'Task not found'
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


        if (isset($data['title'])) {

            if (!is_string($data['title'])) {
                return $this->json([
                    'error' => 'Title must be a string'
                ], 400);
            }


            $task->setTitle($data['title']);
        }


        if (isset($data['description'])) {

            if (!is_string($data['description'])) {
                return $this->json([
                    'error' => 'Description must be a string'
                ], 400);
            }


            $task->setDescription($data['description']);
        }


        if (isset($data['status'])) {

            $status = strtoupper(
                (string) $data['status']
            );


            if (!$this->isValidStatus($status)) {
                return $this->json([
                    'error' => 'Invalid status'
                ], 400);
            }


            $task->setStatus($status);
        }


        if (isset($data['dueDate'])) {

            $dueDate = \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data['dueDate']
            );


            $errors = \DateTimeImmutable::getLastErrors();


            if (
                !$dueDate ||
                ($errors !== false && $errors['warning_count'] > 0) ||
                ($errors !== false && $errors['error_count'] > 0)
            ) {
                return $this->json([
                    'error' => 'Invalid dueDate format. Expected Y-m-d'
                ], 400);
            }


            $task->setDueDate($dueDate);
        }


        $entityManager->flush();


        return $this->json(
            $this->taskToArray($task)
        );
    }


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


        $taskTitle = $task->getTitle();


        $entityManager->remove($task);
        $entityManager->flush();


        return $this->json([
            'message' => "Task '{$taskTitle}' deleted"
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
            'employeeId' => $task->getEmployee()?->getId(),
        ];
    }
}
