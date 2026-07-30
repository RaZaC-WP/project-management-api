<?php

namespace App\OpenApi;

class TaskListResponse
{
    public int $id;
    public string $title;
    public ?string $description = null;
    public string $status;
    public ?string $createdAt = null;
    public ?string $dueDate = null;
    public int $projectId;
    public ?int $employeeId = null;
}


class TaskCreateRequest
{
    public string $title;
    public ?string $description = null;
    public string $status;
    public int $projectId;
    public ?int $employeeId = null;
    public ?string $dueDate = null;
}


class TaskByIdResponse
{
    public int $id;
    public string $title;
    public ?string $description = null;
    public string $status;
    public ?string $createdAt = null;
    public ?string $dueDate = null;
    public int $projectId;
    public ?int $employeeId = null;
}


class TaskUpdateRequest
{
    public ?string $title = null;
    public ?string $description = null;
    public ?string $status = null;
    public ?string $dueDate = null;
}
