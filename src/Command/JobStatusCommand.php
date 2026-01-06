<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use App\Service\JobStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:job-status', description: 'Update the status of a job.')]
class JobStatusCommand extends Command
{
    public function __construct(
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
        private JobStatusService $jobStatusService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Job ID')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'New status (FOUND, CONTACTED, INTERVIEWING, REJECTED, ACCEPTED)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = (int) $input->getOption('id');
        $statusValue = strtoupper((string) $input->getOption('status'));
        $status = ApplicationStatus::tryFrom($statusValue);

        if ($id <= 0 || $status === null) {
            $io->error('Both --id and a valid --status are required.');
            return Command::INVALID;
        }

        $job = $this->jobRepository->find($id);
        if ($job === null) {
            $io->error(sprintf('Job %d not found.', $id));
            return Command::FAILURE;
        }

        $this->jobStatusService->updateStatus($job, $status);
        $this->entityManager->flush();

        $io->success(sprintf('Job %d updated to %s.', $id, $status->value));

        return Command::SUCCESS;
    }
}
