<?php

namespace App\Tests\Controller\Api;

class ProjectControllerTest extends ApiWebTestCase
{
    public function testCreateProjectSuccess(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'New website',
            'startDate' => '2026-01-01',
            'endDate' => '2026-06-30',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(201);

        $data = $this->decodeResponse();
        $this->assertSame('New website', $data['name']);
        $this->assertSame('2026-01-01', $data['startDate']);
        $this->assertSame('2026-06-30', $data['endDate']);
    }

    public function testCreateProjectRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Unauthorized project',
            'startDate' => '2026-01-01',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProjectMissingRequiredFieldsReturns400(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'No start date',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateProjectWithMalformedStartDateReturns400(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Bad date project',
            'startDate' => 'not-a-date',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'Invalid startDate format',
            $this->decodeResponse()['error']
        );
    }

    public function testCreateProjectWithEndDateBeforeStartDateReturns400(): void
    {
        $this->jsonRequest('POST', '/api/projects', [
            'name' => 'Backwards project',
            'startDate' => '2026-06-30',
            'endDate' => '2026-01-01',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(
            'End date cannot be before start date',
            $this->decodeResponse()['error']
        );
    }

    public function testUpdateProjectWithMalformedStartDateReturns400(): void
    {
        // Regression test: update() used to parse dates with `new
        // DateTimeImmutable()`, which silently accepts almost any string
        // instead of rejecting anything that isn't a strict Y-m-d date.
        $project = $this->createProject();

        $this->jsonRequest('PUT', "/api/projects/{$project->getId()}", [
            'startDate' => 'not-a-date',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateProjectWithEndDateBeforeStartDateReturns400(): void
    {
        $project = $this->createProject(startDate: '2026-01-01');

        $this->jsonRequest('PUT', "/api/projects/{$project->getId()}", [
            'endDate' => '2025-12-31',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
    }

    public function testShowProjectNotFoundReturns404(): void
    {
        $this->client->request('GET', '/api/projects/999999', server: $this->authHeaders());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteProjectWithTasksReturns409(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Blocking task',
            'projectId' => $project->getId(),
        ], $this->authHeaders());

        $this->client->request('DELETE', "/api/projects/{$project->getId()}", server: $this->authHeaders());

        $this->assertResponseStatusCodeSame(409);
    }

    public function testDeleteProjectSuccess(): void
    {
        $project = $this->createProject();
        $projectId = $project->getId();

        $this->client->request('DELETE', "/api/projects/{$projectId}", server: $this->authHeaders());
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', "/api/projects/{$projectId}", server: $this->authHeaders());
        $this->assertResponseStatusCodeSame(404);
    }
}
