<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\OpenApi\ProjectListResponse;
use App\OpenApi\ProjectCreateRequest;
use App\OpenApi\ProjectByIdResponse;
use App\OpenApi\ProjectUpdateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/projects')]
#[OA\Tag(name: 'Projects')]
final class ProjectController extends AbstractController
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

        $startDate = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $data['startDate']
        );

        if (!$startDate) {
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

            if (!$endDate) {
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
            $this->projectToArray($project)
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

        if (isset($data['name'])) {
            $project->setName($data['name']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        if (isset($data['startDate'])) {

            $project->setStartDate(
                new \DateTimeImmutable($data['startDate'])
            );
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

        $entityManager->remove($project);
        $entityManager->flush();

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
}