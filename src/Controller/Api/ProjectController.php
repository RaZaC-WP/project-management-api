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


        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }


        if (!isset($data['name'], $data['startDate'])) {
            return $this->json([
                'error' => 'Name and startDate are required'
            ], 400);
        }


        if (!is_string($data['name'])) {
            return $this->json([
                'error' => 'Name must be a string'
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


        $startDate = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $data['startDate']
        );


        $errors = \DateTimeImmutable::getLastErrors();


        if (
            !$startDate ||
            ($errors !== false && $errors['warning_count'] > 0) ||
            ($errors !== false && $errors['error_count'] > 0)
        ) {
            return $this->json([
                'error' => 'Invalid startDate format. Expected Y-m-d'
            ], 400);
        }


        $endDate = null;


        if (isset($data['endDate'])) {

            $endDate = \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data['endDate']
            );


            $errors = \DateTimeImmutable::getLastErrors();


            if (
                !$endDate ||
                ($errors !== false && $errors['warning_count'] > 0) ||
                ($errors !== false && $errors['error_count'] > 0)
            ) {
                return $this->json([
                    'error' => 'Invalid endDate format. Expected Y-m-d'
                ], 400);
            }
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


        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }


        if (isset($data['name'])) {

            if (!is_string($data['name'])) {
                return $this->json([
                    'error' => 'Name must be a string'
                ], 400);
            }

            $project->setName($data['name']);
        }


        if (isset($data['description'])) {

            if (!is_string($data['description'])) {
                return $this->json([
                    'error' => 'Description must be a string'
                ], 400);
            }

            $project->setDescription($data['description']);
        }


        $startDate = $project->getStartDate();
        $endDate = $project->getEndDate();


        if (isset($data['startDate'])) {

            $startDate = \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data['startDate']
            );

            $errors = \DateTimeImmutable::getLastErrors();


            if (
                !$startDate ||
                ($errors !== false && $errors['warning_count'] > 0) ||
                ($errors !== false && $errors['error_count'] > 0)
            ) {
                return $this->json([
                    'error' => 'Invalid startDate format. Expected Y-m-d'
                ], 400);
            }
        }


        if (isset($data['endDate'])) {

            $endDate = \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data['endDate']
            );


            $errors = \DateTimeImmutable::getLastErrors();


            if (
                !$endDate ||
                ($errors !== false && $errors['warning_count'] > 0) ||
                ($errors !== false && $errors['error_count'] > 0)
            ) {
                return $this->json([
                    'error' => 'Invalid endDate format. Expected Y-m-d'
                ], 400);
            }
        }


        if ($endDate && $startDate && $endDate < $startDate) {
            return $this->json([
                'error' => 'End date cannot be before start date'
            ], 400);
        }


        $project->setStartDate($startDate);
        $project->setEndDate($endDate);


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
}
