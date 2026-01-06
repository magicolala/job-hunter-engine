<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ScrapingRun;
use Doctrine\ORM\EntityManagerInterface;

final class ScrapingRunRecorder
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param string[] $sources
     * @param string[] $queries
     */
    public function startRun(array $sources, array $queries): ScrapingRun
    {
        $run = new ScrapingRun();
        $run->setSources($sources);
        $run->setQueries($queries);
        $run->setStatus('RUNNING');

        $this->entityManager->persist($run);
        $this->entityManager->flush();

        return $run;
    }

    public function completeRun(
        ScrapingRun $run,
        int $totalListings,
        int $uniqueListings,
        int $newJobs,
        int $duplicates
    ): void {
        $run->setTotalListings($totalListings);
        $run->setUniqueListings($uniqueListings);
        $run->setNewJobs($newJobs);
        $run->setDuplicates($duplicates);
        $run->setStatus('COMPLETED');
        $run->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
