<?php

declare(strict_types=1);

namespace App\Enum;

enum ApplicationStatus: string
{
    case FOUND = 'FOUND';
    case CONTACTED = 'CONTACTED';
    case INTERVIEWING = 'INTERVIEWING';
    case REJECTED = 'REJECTED';
    case ACCEPTED = 'ACCEPTED';
}
