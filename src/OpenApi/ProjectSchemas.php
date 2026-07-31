<?php

namespace App\OpenApi;

class ProjectListResponse
{
    public int $id = 1;
    public string $name = "Project Management API";

    public ?string $description = "Backend API";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-09-06";
}


class ProjectCreateRequest
{
    public string $name = "Project Management API";

    public ?string $description = "Backend API";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-09-06";

    public array $employeeIds = [1];

    public array $taskIds = [1, 2];
}

class ProjectByIdResponse
{
    public string $name = "Project Management API";

    public ?string $description = "Backend API";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-09-06";

    public array $employeeIds = [1];

    public array $taskIds = [1, 2];
}


class ProjectUpdateRequest
{
    public string $name = "Project Management API";

    public ?string $description = "Backend API";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-09-06";

    public array $employeeIds = [1];

    public array $taskIds = [1, 2];
}