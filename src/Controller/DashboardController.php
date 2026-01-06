<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\JobRepository;
use App\Repository\ScrapingRunRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\KernelInterface;

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
    public function runScrape(Request $request, KernelInterface $kernel): RedirectResponse
    {
        $requestData = $request->request->all();
        $sourcesParam = $requestData['sources'] ?? null;
        if (is_array($sourcesParam)) {
            $sources = $sourcesParam;
        } else {
            $sources = $this->parseCsv((string) $sourcesParam);
        }
        if ($sources === []) {
            $sources = ['wttj', 'remotive'];
        }
        $queries = $this->parseCsv((string) $request->request->get('queries', 'symfony'));
        $region = trim((string) $request->request->get('region', ''));
        $limitValue = $request->request->get('limit');
        $limit = $limitValue !== null ? (int) $limitValue : null;

        $command = [
            'php',
            'bin/console',
            'app:hunt',
            '--sources=' . implode(',', $sources),
            '--queries=' . implode(',', $queries),
        ];

        if ($limit !== null && $limit > 0) {
            $command[] = '--limit=' . $limit;
        }
        if ($region !== '') {
            $command[] = '--region=' . $region;
        }

        $process = new Process($command, $kernel->getProjectDir());
        $process->setTimeout(300);
        $process->run();

        if ($process->isSuccessful()) {
            $this->addFlash('success', 'Scraping lance avec succes.');
        } else {
            $this->addFlash('error', 'Echec du scraping: ' . trim($process->getErrorOutput()));
        }

        return $this->redirectToRoute('dashboard');
    }

    /**
     * @return string[]
     */
    private function parseCsv(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));

        return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
    }
}
