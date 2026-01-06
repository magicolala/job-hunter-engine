<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\JobRepository;
use App\Repository\ScrapingRunRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(JobRepository $jobRepository, ScrapingRunRepository $scrapingRunRepository): Response
    {
        $totalJobs = $jobRepository->count([]);
        $recentJobs = $jobRepository->findBy([], ['createdAt' => 'DESC'], 20);
        $pipeline = $jobRepository->getPipelineSummary();
        foreach ($pipeline as &$row) {
            if (isset($row['status']) && $row['status'] instanceof \BackedEnum) {
                $row['status'] = $row['status']->value;
            } elseif (isset($row['status'])) {
                $row['status'] = (string) $row['status'];
            }
        }
        unset($row);
        $recentRuns = $scrapingRunRepository->findBy([], ['startedAt' => 'DESC'], 5);

        $scores = [];
        $techCounts = [];
        foreach ($recentJobs as $job) {
            if ($job->getRelevanceScore() !== null) {
                $scores[] = $job->getRelevanceScore();
            }

            foreach ($job->getTechnologies() as $technology) {
                $key = strtolower($technology);
                $techCounts[$key] = ($techCounts[$key] ?? 0) + 1;
            }
        }

        arsort($techCounts);
        $topTechs = array_slice($techCounts, 0, 6, true);
        $avgScore = $scores === [] ? null : (int) round(array_sum($scores) / count($scores));
        $latestRun = $recentRuns[0] ?? null;

        return $this->render('dashboard/index.html.twig', [
            'totalJobs' => $totalJobs,
            'recentJobs' => $recentJobs,
            'pipeline' => $pipeline,
            'recentRuns' => $recentRuns,
            'topTechs' => $topTechs,
            'avgScore' => $avgScore,
            'latestRun' => $latestRun,
        ]);
    }

    #[Route('/dashboard/run-scrape', name: 'dashboard_run_scrape', methods: ['POST'])]
    public function runScrape(): RedirectResponse
    {
        $process = new Process(['php', 'bin/console', 'app:hunt']);
        $process->setTimeout(300);
        $process->run();

        if ($process->isSuccessful()) {
            $this->addFlash('success', 'Scraping lance avec succes.');
        } else {
            $this->addFlash('error', 'Echec du scraping: ' . trim($process->getErrorOutput()));
        }

        return $this->redirectToRoute('dashboard');
    }
}
