<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:jobs', description: 'List stored jobs with filters.')]
class JobListCommand extends Command
{
    public function __construct(private JobRepository $jobRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('technologies', null, InputOption::VALUE_OPTIONAL, 'Comma-separated technologies.')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Recency window in days.', 30)
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Filter by status (FOUND, CONTACTED, INTERVIEWING, REJECTED, ACCEPTED).')
            ->addOption('min-score', null, InputOption::VALUE_OPTIONAL, 'Minimum relevance score.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $technologies = $this->parseCsv((string) $input->getOption('technologies'));
        $days = max(0, (int) $input->getOption('days'));
        $publishedSince = $days > 0 ? new DateTimeImmutable(sprintf('-%d days', $days)) : null;
        $status = $this->parseStatus((string) $input->getOption('status'));
        $minScoreOption = $input->getOption('min-score');
        $minScore = $minScoreOption !== null ? (int) $minScoreOption : null;

        $jobs = $this->jobRepository->findByFilters($technologies, $publishedSince, $status, $minScore);

        if ($jobs === []) {
            $io->warning('No jobs matched the filters.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($jobs as $job) {
            $rows[] = [
                $job->getCompany(),
                $job->getTitle(),
                (string) ($job->getRelevanceScore() ?? 'n/a'),
                $job->getStatus()->value,
            ];
        }

        $io->table(['Company', 'Title', 'Score', 'Status'], $rows);
        $io->success(sprintf('Returned %d job(s).', count($jobs)));

        return Command::SUCCESS;
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
