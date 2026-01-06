<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JobsController extends AbstractController
{
    #[Route('/jobs', name: 'jobs')]
    public function index(Request $request, JobRepository $jobRepository): Response
    {
        $technologies = $this->parseCsv((string) $request->query->get('technologies', ''));
        $days = max(0, (int) $request->query->get('days', 30));
        $publishedSince = $days > 0 ? new DateTimeImmutable(sprintf('-%d days', $days)) : null;
        $status = $this->parseStatus((string) $request->query->get('status', ''));
        $minScoreValue = $request->query->get('minScore');
        $minScore = $minScoreValue !== null ? (int) $minScoreValue : null;

        $jobs = $jobRepository->findByFilters($technologies, $publishedSince, $status, $minScore);

        return $this->render('jobs/index.html.twig', [
            'jobs' => $jobs,
            'technologies' => $technologies,
            'days' => $days,
            'status' => $status?->value,
            'minScore' => $minScore,
        ]);
    }

    /**
     * @return string[]
     */
    private function parseCsv(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));

        return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
    }

    private function parseStatus(string $value): ?ApplicationStatus
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return null;
        }

        return ApplicationStatus::tryFrom($value);
    }
}
