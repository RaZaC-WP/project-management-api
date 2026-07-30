<?php

namespace App\OpenApi;

class ProjectListResponse
{
    public int $id;
    public string $name;
    public ?string $description = null;
    public string $startDate;
    public ?string $endDate = null;
}


class ProjectCreateRequest
{
    public string $name;
    public ?string $description = null;
    public string $startDate;
    public ?string $endDate = null;
}


class ProjectByIdResponse
{
    public int $id;
    public string $name;
    public ?string $description = null;
    public string $startDate;
    public ?string $endDate = null;
}


class ProjectUpdateRequest
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
}