<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Job;
use App\Repository\JobRepository;
use App\Service\CsvExporter;
use App\Service\JobDeduplicator;
use App\Service\JobIngestionService;
use App\Service\JobScraper;
use App\Service\LeadFinder;
use App\Service\ProfileCriteriaService;
use App\Service\ScrapingRunRecorder;
use App\Service\SourceRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\ExecutableFinder;

#[AsCommand(name: 'app:hunt', description: 'Scrape jobs, enrich leads and export a LinkedIn CSV.')]
class JobHuntCommand extends Command
{
    private const DEFAULT_URL = 'https://www.welcometothejungle.com/fr/jobs?query=symfony';
    private const DEFAULT_QUERY = 'symfony';
    private const DEFAULT_OUTPUT = 'var/export_linkedin.csv';

    public function __construct(
        private JobScraper $scraper,
        private LeadFinder $leadFinder,
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
        private CsvExporter $csvExporter,
        private SourceRegistry $sourceRegistry,
        private JobDeduplicator $deduplicator,
        private ScrapingRunRecorder $scrapingRunRecorder,
        private ProfileCriteriaService $profileCriteriaService,
        private JobIngestionService $jobIngestionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sources', null, InputOption::VALUE_OPTIONAL, 'Comma-separated sources to use.', 'wttj')
            ->addOption('queries', null, InputOption::VALUE_OPTIONAL, 'Comma-separated search queries.', self::DEFAULT_QUERY)
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Search URL to scrape.', self::DEFAULT_URL)
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source to use: wttj, wttj-api, or remotive.', 'wttj')
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search query for API sources.', self::DEFAULT_QUERY)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Max number of listings to process.')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, 'Region filter (source-specific).')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'CSV output path.', self::DEFAULT_OUTPUT)
            ->addOption('no-enrich', null, InputOption::VALUE_NONE, 'Skip Apollo enrichment.')
            ->addOption('throttle-ms', null, InputOption::VALUE_OPTIONAL, 'Delay between enrich calls in ms.', 0)
            ->addOption('debug-scrape', null, InputOption::VALUE_NONE, 'Save scrape debug artifacts.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Job Hunter Engine');

        $url = (string) $input->getOption('url');
        $legacySource = strtolower((string) $input->getOption('source'));
        $legacyQuery = (string) $input->getOption('query');
        $sourcesOption = (string) $input->getOption('sources');
        $queriesOption = (string) $input->getOption('queries');
        $region = (string) $input->getOption('region');
        $sources = $this->sourceRegistry->normalizeSources(
            $sourcesOption !== '' ? $this->parseCsvList($sourcesOption) : [$legacySource]
        );
        $queries = $queriesOption !== '' ? $this->parseCsvList($queriesOption) : [$legacyQuery];
        $limitOption = $input->getOption('limit');
        $limit = $limitOption !== null ? max(0, (int) $limitOption) : null;
        if ($limit === 0) {
            $limit = null;
        }

        $outputPath = (string) $input->getOption('output');
        if ($outputPath === '') {
            $outputPath = self::DEFAULT_OUTPUT;
        }

        $skipEnrich = (bool) $input->getOption('no-enrich');
        $throttleMs = max(0, (int) $input->getOption('throttle-ms'));
        $debugScrape = (bool) $input->getOption('debug-scrape');

        if ($skipEnrich) {
            $io->note('Lead enrichment disabled (--no-enrich).');
        } elseif (!$this->leadFinder->isEnabled()) {
            $io->warning('APOLLO_API_KEY is empty; lead enrichment will be skipped.');
            $skipEnrich = true;
        }

        if ($io->isVerbose()) {
            $this->writeDebugInfo($io, $url, implode(',', $sources), implode(',', $queries), $limit, $outputPath, $skipEnrich, $throttleMs, $debugScrape);
        }

        $run = $this->scrapingRunRecorder->startRun($sources, $queries);
        $listings = $this->scraper->scrapeSources($sources, $queries, $limit, $debugScrape, $url, $region);
        $total = count($listings);
        $existingExternalIds = $this->jobRepository->findExistingExternalIds(
            array_values(array_filter(array_map(
                static fn (array $listing): string => (string) ($listing['externalId'] ?? ''),
                $listings
            )))
        );
        $deduped = $this->deduplicator->deduplicate($listings);
        $unique = count($deduped);
        $duplicates = $total - $unique;
        $io->text(sprintf('Scraped %d listings (%d unique).', $total, $unique));

        if ($debugScrape) {
            $io->note('Debug artifacts written to var/debug.');
        }

        $exportRows = [];
        $created = 0;
        $updated = 0;
        $total = $unique;
        $progress = null;
        $criteria = $this->profileCriteriaService->getCurrent();

        if ($total > 0) {
            $progress = new ProgressBar($output, $total);
            $progress->start();
        }

        foreach ($deduped as $listing) {
            $listing = $this->jobIngestionService->enrichListing($listing, $criteria);
            $externalId = $listing['externalId'] !== '' ? $listing['externalId'] : null;
            $job = null;
            if ($externalId !== null && in_array($externalId, $existingExternalIds, true)) {
                $job = $this->jobRepository->findOneBy(['externalId' => $externalId]);
            }

            if ($job === null) {
                $job = (new Job())
                    ->setExternalId($externalId)
                    ->setSource($listing['source'] ?? $legacySource)
                    ->setCompany($listing['company'])
                    ->setTitle($listing['title'])
                    ->setJobUrl($listing['jobUrl'] !== '' ? $listing['jobUrl'] : null);
                $created++;
            } else {
                $job->setSource($listing['source'] ?? $legacySource);
                $job->setCompany($listing['company']);
                $job->setTitle($listing['title']);
                $job->setJobUrl($listing['jobUrl'] !== '' ? $listing['jobUrl'] : null);
                $updated++;
            }

            $this->jobIngestionService->applyToJob($job, $listing);

            $contact = [];
            if (!$skipEnrich) {
                $domain = $this->guessCompanyDomain($listing['company']);
                $contact = $domain !== '' ? $this->leadFinder->findCTO($domain) : [];
            }

            if ($contact !== []) {
                $contactName = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
                $job->setContactName($contactName !== '' ? $contactName : null);
                $job->setContactLinkedin($contact['linkedin_url'] ?? null);

                $exportRows[] = [
                    $contact['linkedin_url'] ?? '',
                    $contact['first_name'] ?? 'Team',
                    $contact['last_name'] ?? 'Tech',
                    $listing['company'],
                    $contact['title'] ?? 'Lead Dev',
                ];
            }

            if (!$skipEnrich && $throttleMs > 0) {
                usleep($throttleMs * 1000);
            }

            $this->entityManager->persist($job);

            if ($progress) {
                $progress->advance();
            }
        }

        if ($progress) {
            $progress->finish();
            $io->newLine(2);
        }

        $this->entityManager->flush();
        $csvPath = $this->csvExporter->export($exportRows, $outputPath);
        $this->scrapingRunRecorder->completeRun($run, $total, $unique, $created, $duplicates);

        $io->success(sprintf('Created %d new jobs, updated %d, skipped %d duplicates.', $created, $updated, $duplicates));
        $io->text(sprintf('CSV export ready: %s', $csvPath));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function guessCompanyDomain(string $company): string
    {
        $slug = strtolower(trim($company));
        $slug = preg_replace('/[^a-z0-9]+/i', '', $slug) ?? '';

        if ($slug === '') {
            return '';
        }

        return $slug . '.com';
    }

    private function writeDebugInfo(
        SymfonyStyle $io,
        string $url,
        string $source,
        string $query,
        ?int $limit,
        string $outputPath,
        bool $skipEnrich,
        int $throttleMs,
        bool $debugScrape
    ): void {
        $finder = new ExecutableFinder();
        $chromedriver = $finder->find('chromedriver', null, ['./drivers', './vendor/bin']);

        $io->section('Debug info');
        $io->listing([
            sprintf('URL: %s', $url),
            sprintf('Source: %s', $source),
            sprintf('Query: %s', $query),
            sprintf('Limit: %s', $limit === null ? 'none' : (string) $limit),
            sprintf('Output: %s', $outputPath),
            sprintf('Enrichment: %s', $skipEnrich ? 'disabled' : 'enabled'),
            sprintf('Throttle: %d ms', $throttleMs),
            sprintf('Debug scrape: %s', $debugScrape ? 'enabled' : 'disabled'),
            sprintf('APOLLO_API_KEY: %s', $this->leadFinder->isEnabled() ? 'set' : 'empty'),
            sprintf('WTTJ_ALGOLIA_APP_ID: %s', $_SERVER['WTTJ_ALGOLIA_APP_ID'] ?? 'not set'),
            sprintf('WTTJ_ALGOLIA_API_KEY: %s', isset($_SERVER['WTTJ_ALGOLIA_API_KEY']) && $_SERVER['WTTJ_ALGOLIA_API_KEY'] !== '' ? 'set' : 'empty'),
            sprintf('WTTJ_ALGOLIA_INDEX: %s', $_SERVER['WTTJ_ALGOLIA_INDEX'] ?? 'wttj_jobs_production_fr'),
            sprintf('PANTHER_CHROME_BINARY: %s', $_SERVER['PANTHER_CHROME_BINARY'] ?? 'not set'),
            sprintf('PANTHER_CHROME_DRIVER_BINARY: %s', $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] ?? 'not set'),
            sprintf('chromedriver (found): %s', $chromedriver ?? 'not found'),
            sprintf('drivers/chromedriver: %s', file_exists('drivers/chromedriver') ? 'present' : 'missing'),
            sprintf('chromedriver log: %s', (getcwd() ?: '.') . '/var/chromedriver.log'),
        ]);
    }

    /**
     * @return string[]
     */
    private function parseCsvList(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));

        return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
    }
}
