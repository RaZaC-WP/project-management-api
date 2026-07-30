<?php

namespace App\OpenApi;

class EmployeeListResponse
{
    public int $id;
    public string $fullName;
    public string $email;
    public string $position;
}

class EmployeeCreateRequest
{
    public int $id;
    public string $fullName;
    public string $email;
    public string $position;

}

class EmployeeByIdResponse
{
    public string $fullName;
    public string $email;
    public string $position;
}

class EmployeeUpdateRequest
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $position = null;
}
