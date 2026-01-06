<?php

declare(strict_types=1);

namespace App\Service;

final class JobDeduplicator
{
    /**
     * @param array<int, array<string, mixed>> $jobs
     * @param string[] $existingExternalIds
     * @return array<int, array<string, mixed>>
     */
    public function deduplicate(array $jobs, array $existingExternalIds = []): array
    {
        $seen = array_fill_keys($existingExternalIds, true);
        $deduped = [];

        foreach ($jobs as $job) {
            $externalId = isset($job['externalId']) ? (string) $job['externalId'] : '';
            $key = $externalId !== '' ? $externalId : $this->buildFallbackKey($job);

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $job;
        }

        return $deduped;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function buildFallbackKey(array $job): string
    {
        $company = isset($job['company']) ? (string) $job['company'] : '';
        $title = isset($job['title']) ? (string) $job['title'] : '';
        $location = isset($job['location']) ? (string) $job['location'] : '';
        $jobUrl = isset($job['jobUrl']) ? (string) $job['jobUrl'] : '';

        $seed = trim($jobUrl) !== '' ? $jobUrl : $company . '|' . $title . '|' . $location;

        return $seed !== '' ? hash('sha256', $seed) : '';
    }
}
