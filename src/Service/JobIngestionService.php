<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Job;
use App\Entity\ProfileCriteria;

final class JobIngestionService
{
    public function __construct(
        private TechnologyExtractor $technologyExtractor,
        private ScoringService $scoringService
    ) {
    }

    /**
     * @param array<string, mixed> $listing
     * @return array<string, mixed>
     */
    public function enrichListing(array $listing, ProfileCriteria $criteria): array
    {
        $description = (string) ($listing['description'] ?? '');
        $technologies = $description !== '' ? $this->technologyExtractor->extract($description) : [];

        $score = $this->scoringService->score($criteria, [
            'technologies' => $technologies,
            'location' => $listing['location'] ?? null,
            'seniority' => $listing['seniority'] ?? null,
        ]);

        $listing['technologies'] = $technologies;
        $listing['relevanceScore'] = $score;

        return $listing;
    }

    /**
     * @param array<string, mixed> $listing
     */
    public function applyToJob(Job $job, array $listing): Job
    {
        $job->setTechnologies($listing['technologies'] ?? []);
        $job->setRelevanceScore($listing['relevanceScore'] ?? null);
        $job->setLocation($listing['location'] ?? null);
        $job->setDescription($listing['description'] ?? null);

        return $job;
    }
}
