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

    public ?string $description = "Backend API developed with Symfony 7.4";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-12-31";

    public array $employees = [
        [
            "id" => 1,
            "fullName" => "Javier Molinos",
            "email" => "javi@gmail.com",
            "position" => "Backend Developer",
        ],
        [
            "id" => 2,
            "fullName" => "Laura Pérez",
            "email" => "laura@gmail.com",
            "position" => "Frontend Developer",
        ],
    ];

    public array $tasks = [
        [
            "id" => 1,
            "title" => "Implement JWT authentication",
            "status" => "DONE",
        ],
        [
            "id" => 2,
            "title" => "Create REST endpoints",
            "status" => "IN_PROGRESS",
        ],
    ];
}

class ProjectByIdResponse
{
    public int $id = 1;

    public string $name = "Project Management API";

    public ?string $description = "Backend API developed with Symfony 7.4";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-12-31";

    public array $employees = [
        [
            "id" => 1,
            "fullName" => "Javier Molinos",
            "email" => "javi@gmail.com",
            "position" => "Backend Developer",
        ],
        [
            "id" => 2,
            "fullName" => "Laura Pérez",
            "email" => "laura@gmail.com",
            "position" => "Frontend Developer",
        ],
    ];

    public array $tasks = [
        [
            "id" => 1,
            "title" => "Implement JWT authentication",
            "status" => "DONE",
        ],
        [
            "id" => 2,
            "title" => "Create REST endpoints",
            "status" => "IN_PROGRESS",
        ],
    ];
}


class ProjectUpdateRequest
{
    public string $name = "Project Management API";

    public ?string $description = "Backend API developed with Symfony 7.4";

    public string $startDate = "2026-08-01";

    public ?string $endDate = "2026-12-31";

    public array $employees = [
        [
            "id" => 1,
            "fullName" => "Javier Molinos",
            "email" => "javi@gmail.com",
            "position" => "Backend Developer",
        ],
        [
            "id" => 2,
            "fullName" => "Laura Pérez",
            "email" => "laura@gmail.com",
            "position" => "Frontend Developer",
        ],
    ];

    public array $tasks = [
        [
            "id" => 1,
            "title" => "Implement JWT authentication",
            "status" => "DONE",
        ],
        [
            "id" => 2,
            "title" => "Create REST endpoints",
            "status" => "IN_PROGRESS",
        ],
    ];
}