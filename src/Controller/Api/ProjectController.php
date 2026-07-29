<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
final class ProjectController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAll();

        return $this->json(
            array_map(
                fn(Project $project) => $this->projectToArray($project),
                $projects
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

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        if (!isset($data['name'], $data['startDate'])) {
            return $this->json([
                'error' => 'Name and startDate are required'
            ], 400);
        }

        try {
            $startDate = new \DateTimeImmutable($data['startDate']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Invalid startDate format'
            ], 400);
        }

        $project = new Project();

        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? null);
        $project->setStartDate($startDate);

        $entityManager->persist($project);
        $entityManager->flush();

        return $this->json(
            $this->projectToArray($project),
            201
        );
    }

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
            $this->projectToArray($project)
        );
    }

    private function projectToArray(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
        ];
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ProjectRepository $projectRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $project = $projectRepository->find($id);

        if (!$project) {
            return $this->json([
                'error' => 'Project not found'
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

        if (isset($data['name'])) {
            $project->setName($data['name']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['startDate'])) {
            try {
                $project->setStartDate(
                    new \DateTimeImmutable($data['startDate'])
                );
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Invalid startDate format'
                ], 400);
            }
        }

        if (isset($data['endDate'])) {
            $project->setEndDate(
                new \DateTimeImmutable($data['endDate'])
            );
        }

        $entityManager->flush();

        return $this->json(
            $this->projectToArray($project)
        );
    }

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
        $projectName = $project->getName();

        $entityManager->remove($project);
        $entityManager->flush();

        return $this->json([
            'message' => "Project: '{$projectName}' deleted"
        ]);
    }
}