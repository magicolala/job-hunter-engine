<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Job;
use App\Enum\ApplicationStatus;
use DateTimeImmutable;

final class JobStatusService
{
    public function updateStatus(Job $job, ApplicationStatus $status): Job
    {
        $job->setStatus($status);
        $job->setStatusUpdatedAt(new DateTimeImmutable());

        return $job;
    }
}
