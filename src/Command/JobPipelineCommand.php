<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\JobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:job-pipeline', description: 'Show job pipeline status counts.')]
class JobPipelineCommand extends Command
{
    public function __construct(private JobRepository $jobRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $summary = $this->jobRepository->getPipelineSummary();

        if ($summary === []) {
            $io->warning('No jobs found.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($summary as $row) {
            $rows[] = [$row['status'], $row['count']];
        }

        $io->table(['Status', 'Count'], $rows);

        return Command::SUCCESS;
    }
}
