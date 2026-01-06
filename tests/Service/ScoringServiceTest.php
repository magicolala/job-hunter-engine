<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ProfileCriteria;
use App\Service\ScoringService;
use PHPUnit\Framework\TestCase;

class ScoringServiceTest extends TestCase
{
    public function testScoresBasedOnCriteria(): void
    {
        $criteria = new ProfileCriteria();
        $criteria->setPreferredTechnologies(['symfony', 'php']);
        $criteria->setSeniority('senior');
        $criteria->setLocations(['Paris']);
        $criteria->setRemoteOnly(false);

        $service = new ScoringService();
        $score = $service->score($criteria, [
            'technologies' => ['symfony'],
            'location' => 'Paris',
            'seniority' => 'senior',
        ]);

        $this->assertSame(75, $score);
    }
}
