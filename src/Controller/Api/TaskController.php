<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks')]
final class TaskController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        TaskRepository $taskRepository
    ): JsonResponse {

        $tasks = $taskRepository->findAll();

        return $this->json(
            array_map(
                fn(Task $task) => $this->taskToArray($task),
                $tasks
            )
        );
    }


    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ProjectRepository $projectRepository,
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


        $project = $projectRepository->find(
            $data['projectId']
        );


        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
            ], 404);
        }


        $task = new Task();

        $task->setTitle($data['title']);
        $task->setDescription(
            $data['description'] ?? null
        );

        $task->setStatus(
            $data['status'] ?? 'PENDING'
        );


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
            $task->setStatus(
                $data['status']
            );
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