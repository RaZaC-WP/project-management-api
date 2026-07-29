<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Task',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'title',
            type: 'string',
            example: 'Implementar autenticación JWT'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Añadir seguridad con Symfony Security'
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            example: 'IN_PROGRESS'
        ),
        new OA\Property(
            property: 'createdAt',
            type: 'string',
            example: '2026-07-29'
        ),
        new OA\Property(
            property: 'dueDate',
            type: 'string',
            example: '2026-08-15'
        ),
        new OA\Property(
            property: 'projectId',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'employeeId',
            type: 'integer',
            nullable: true,
            example: 5
        )
    ]
)]
class TaskSchema
{
}