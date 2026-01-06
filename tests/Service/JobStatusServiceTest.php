<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Job;
use App\Enum\ApplicationStatus;
use App\Service\JobStatusService;
use PHPUnit\Framework\TestCase;

class JobStatusServiceTest extends TestCase
{
    public function testUpdatesStatusAndTimestamp(): void
    {
        $job = new Job();
        $job->setSource('wttj');
        $job->setCompany('Acme');
        $job->setTitle('Engineer');

        $service = new JobStatusService();
        $service->updateStatus($job, ApplicationStatus::CONTACTED);

        $this->assertSame(ApplicationStatus::CONTACTED, $job->getStatus());
        $this->assertNotNull($job->getStatusUpdatedAt());
    }
}
