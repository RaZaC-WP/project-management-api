<?php

namespace App\Tests\Controller\Api;

class TaskControllerTest extends ApiWebTestCase
{
    public function testCreateTaskSuccess(): void
    {
        $project = $this->createProject();
        $employee = $this->createEmployee();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Write the onboarding docs',
            'projectId' => $project->getId(),
            'employeeId' => $employee->getId(),
            'dueDate' => '2026-12-31',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(201);

        $data = $this->decodeResponse();

        $this->assertSame('Write the onboarding docs', $data['title']);
        $this->assertSame('PENDING', $data['status']);
        $this->assertSame($project->getId(), $data['projectId']);
        $this->assertSame($employee->getId(), $data['employeeId']);
        $this->assertSame('2026-12-31', $data['dueDate']);
    }

    public function testCreateTaskRequiresAuthentication(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Unauthorized task',
            'projectId' => $project->getId(),
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTaskMissingRequiredFields(): void
    {
        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Missing projectId',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(
            'Title and projectId are required',
            $this->decodeResponse()['error']
        );
    }

    public function testCreateTaskWithUnknownProjectReturns404(): void
    {
        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Orphan task',
            'projectId' => 999999,
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateTaskWithInvalidStatusReturns400(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Bad status',
            'projectId' => $project->getId(),
            'status' => 'NOT_A_REAL_STATUS',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateTaskWithMalformedDueDateReturns400(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Bad due date',
            'projectId' => $project->getId(),
            'dueDate' => '31/12/2026',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'Invalid dueDate format',
            $this->decodeResponse()['error']
        );
    }

    public function testCreateTaskWithDueDateBeforeCreationReturns400(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Time traveling task',
            'projectId' => $project->getId(),
            'dueDate' => '2000-01-01',
        ], $this->authHeaders());

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(
            'Due date cannot be before creation date',
            $this->decodeResponse()['error']
        );
    }

    public function testShowTaskNotFoundReturns404(): void
    {
        $this->client->request('GET', '/api/tasks/999999', server: $this->authHeaders());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateTaskDoesNotWipeDueDateWhenFieldIsOmitted(): void
    {
        // Regression test: updating a task without sending `dueDate`
        // must NOT reset the existing due date to null.
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Keep my due date',
            'projectId' => $project->getId(),
            'dueDate' => '2026-12-31',
        ], $this->authHeaders());

        $taskId = $this->decodeResponse()['id'];

        $this->jsonRequest('PUT', "/api/tasks/{$taskId}", [
            'title' => 'Keep my due date (renamed)',
        ], $this->authHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertSame('2026-12-31', $this->decodeResponse()['dueDate']);
    }

    public function testUpdateTaskCanExplicitlyClearDueDate(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Clear my due date',
            'projectId' => $project->getId(),
            'dueDate' => '2026-12-31',
        ], $this->authHeaders());

        $taskId = $this->decodeResponse()['id'];

        $this->jsonRequest('PUT', "/api/tasks/{$taskId}", [
            'dueDate' => null,
        ], $this->authHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->decodeResponse()['dueDate']);
    }

    public function testDeleteTaskSuccess(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'To be deleted',
            'projectId' => $project->getId(),
        ], $this->authHeaders());

        $taskId = $this->decodeResponse()['id'];

        $this->client->request('DELETE', "/api/tasks/{$taskId}", server: $this->authHeaders());
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', "/api/tasks/{$taskId}", server: $this->authHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testFilterTasksByInvalidProjectIdReturns400(): void
    {
        $this->client->request(
            'GET',
            '/api/tasks?project=not-a-number',
            server: $this->authHeaders()
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testFilterTasksByStatus(): void
    {
        $project = $this->createProject();

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Done task',
            'projectId' => $project->getId(),
            'status' => 'done',
        ], $this->authHeaders());

        $this->jsonRequest('POST', '/api/tasks', [
            'title' => 'Pending task',
            'projectId' => $project->getId(),
        ], $this->authHeaders());

        $this->client->request('GET', '/api/tasks?status=done', server: $this->authHeaders());

        $this->assertResponseIsSuccessful();
        $titles = array_column($this->decodeResponse(), 'title');

        $this->assertContains('Done task', $titles);
        $this->assertNotContains('Pending task', $titles);
    }
}
