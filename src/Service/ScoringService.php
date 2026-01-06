<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ProfileCriteria;

final class ScoringService
{
    /**
     * @param array<string, mixed> $jobData
     */
    public function score(ProfileCriteria $criteria, array $jobData): int
    {
        $score = 0;

        $preferred = array_map('strtolower', $criteria->getPreferredTechnologies());
        $jobTechs = array_map('strtolower', $jobData['technologies'] ?? []);

        if ($preferred !== []) {
            $overlap = array_intersect($preferred, $jobTechs);
            $ratio = count($overlap) / count($preferred);
            $score += (int) round(50 * $ratio);
        }

        $jobLocation = strtolower((string) ($jobData['location'] ?? ''));
        $locations = array_map('strtolower', $criteria->getLocations());
        if ($criteria->isRemoteOnly()) {
            if (str_contains($jobLocation, 'remote')) {
                $score += 30;
            }
        } elseif ($locations === [] || in_array($jobLocation, $locations, true)) {
            $score += 30;
        }

        $jobSeniority = strtolower((string) ($jobData['seniority'] ?? ''));
        if ($jobSeniority !== '' && $jobSeniority === strtolower($criteria->getSeniority())) {
            $score += 20;
        }

        return min(100, $score);
    }
}
