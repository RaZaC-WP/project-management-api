<?php

namespace App\OpenApi;

class EmployeeListResponse
{
    public int $id = 1;
    public string $fullName = "Pedro Laguna";
    public string $email = "PedroLaguna@gmail.com";
    public string $position = "Desarrollador";
}

class EmployeeCreateRequest
{

    public string $fullName = "Javier Molinos";
    public string $email = "javi@gmail.com";
    public string $position = "Backend Developer";

}

class EmployeeByIdResponse
{
    public int $id = 1;

    public string $fullName = "Javier Molinos";

    public string $email = "javi@gmail.com";

    public string $position = "Backend Developer";

    public array $projects = [
        [
            "id" => 1,
            "name" => "Project Management API",
            "description" => "Backend API developed with Symfony 7.4"
        ],
        [
            "id" => 2,
            "name" => "Moodle Migration",
            "description" => "Migration and upgrade of Moodle platform"
        ]
    ];

    public array $tasks = [
        [
            "id" => 1,
            "title" => "Implement JWT authentication",
            "status" => "DONE"
        ],
        [
            "id" => 2,
            "title" => "Create REST endpoints",
            "status" => "IN_PROGRESS"
        ]
    ];
}

class EmployeeUpdateRequest
{
    public int $id = 1;
    
    public string $fullName = "Javier Molinos";

    public string $email = "javi@gmail.com";

    public string $position = "Backend Developer";
}
