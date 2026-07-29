<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks')]
final class TaskController extends AbstractController
{
    private const ALLOWED_STATUSES = [
        'PENDING',
        'IN_PROGRESS',
        'DONE'
    ];

    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        TaskRepository $taskRepository
    ): JsonResponse {

        $tasks = $taskRepository->findByFilters(
            $request->query->get('status'),
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

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        if (!isset($data['title'], $data['projectId'])) {
            return $this->json([
                'error' => 'Title and projectId are required'
            ], 400);
        }

        $status = strtoupper($data['status'] ?? 'PENDING');

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
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

            try {
                $task->setDueDate(
                    new \DateTimeImmutable($data['dueDate'])
                );
            } catch (\Exception $e) {

                return $this->json([
                    'error' => 'Invalid dueDate format'
                ], 400);
            }
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


        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
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

            $status = strtoupper($data['status']);

            if (!in_array($status, self::ALLOWED_STATUSES, true)) {
                return $this->json([
                    'error' => 'Invalid status'
                ], 400);
            }

            $task->setStatus($status);
        }


        if (isset($data['dueDate'])) {

            try {
                $task->setDueDate(
                    new \DateTimeImmutable($data['dueDate'])
                );

            } catch (\Exception $e) {

                return $this->json([
                    'error' => 'Invalid dueDate format'
                ], 400);
            }
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