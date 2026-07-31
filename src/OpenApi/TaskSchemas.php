<?php

namespace App\OpenApi;

class TaskListResponse
{
    public int $id = 1;

    public string $title = "Implementar autenticación JWT";

    public ?string $description = "Crear sistema de autenticación mediante tokens JWT";

    public string $status = "IN_PROGRESS";

    public ?string $createdAt = "2026-07-29";

    public ?string $dueDate = "2026-08-15";

    public int $projectId = 1;

    public ?int $employeeId = 2;
}


class TaskCreateRequest
{
    public string $title = "Implementar autenticación JWT";

    public ?string $description = "Crear sistema de autenticación mediante tokens JWT";

    public string $status = "PENDING";

    public int $projectId = 1;

    public ?int $employeeId = 2;

    public ?string $dueDate = "2026-08-15";
}


class TaskByIdResponse
{
    public int $id = 1;

    public string $title = "Implementar autenticación JWT";

    public ?string $description = "Crear sistema de autenticación mediante tokens JWT";

    public string $status = "IN_PROGRESS";

    public ?string $createdAt = "2026-07-29";

    public ?string $dueDate = "2026-08-15";

    public int $projectId = 1;

    public ?int $employeeId = 2;
}


class TaskUpdateRequest
{
    public ?string $title = "Actualizar autenticación JWT";

    public ?string $description = "Modificar configuración de seguridad";

    public ?string $status = "DONE";

    public ?string $dueDate = "2026-08-20";

    public ?int $projectId = 2;

    public ?int $employeeId = 3;
}