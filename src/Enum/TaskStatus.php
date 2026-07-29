<?php

namespace App\Enum;

enum TaskStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DONE = 'DONE';
}