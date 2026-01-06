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
        $title = (string) ($listing['title'] ?? '');
        $seniority = $listing['seniority'] ?? $this->detectSeniority($title);

        $score = $this->scoringService->score($criteria, [
            'technologies' => $technologies,
            'location' => $listing['location'] ?? null,
            'seniority' => $seniority,
        ]);

        $listing['technologies'] = $technologies;
        $listing['relevanceScore'] = $score;
        if ($seniority !== null) {
            $listing['seniority'] = $seniority;
        }

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

        $publishedAt = $listing['publishedAt'] ?? null;
        if (is_string($publishedAt) && $publishedAt !== '') {
            try {
                $job->setPublishedAt(new \DateTimeImmutable($publishedAt));
            } catch (\Throwable) {
            }
        }

        return $job;
    }

    private function detectSeniority(string $title): ?string
    {
        $value = strtolower($title);

        if (str_contains($value, 'lead') || str_contains($value, 'principal')
            || str_contains($value, 'staff') || str_contains($value, 'head')
            || str_contains($value, 'manager')) {
            return 'lead';
        }

        if (str_contains($value, 'senior') || str_contains($value, 'sr')) {
            return 'senior';
        }

        if (str_contains($value, 'junior') || str_contains($value, 'jr')) {
            return 'junior';
        }

        if (str_contains($value, 'mid') || str_contains($value, 'intermediate')) {
            return 'mid';
        }

        return null;
    }
}
