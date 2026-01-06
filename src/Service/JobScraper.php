<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Panther\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class JobScraper
{
    private const BASE_URL = 'https://www.welcometothejungle.com';
    private const REMOTIVE_URL = 'https://remotive.com/api/remote-jobs';
    private const WTTJ_API_REFERER = 'https://www.welcometothejungle.com/';

    public function __construct(
        private HttpClientInterface $httpClient,
        private PaginationPolicy $paginationPolicy,
        private RateLimiter $rateLimiter
    )
    {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeWttj(string $url, ?int $limit = null, bool $debug = false): array
    {
        $projectRoot = getcwd() ?: '.';
        $filesystem = new Filesystem();
        $chromeDriverBinary = $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] ?? null;
        $defaultDriver = $projectRoot . '/drivers/chromedriver';

        if ($chromeDriverBinary === null && is_file($defaultDriver)) {
            $chromeDriverBinary = $defaultDriver;
        }

        $logPath = $projectRoot . '/var/chromedriver.log';
        $managerOptions = [
            'chromedriver_arguments' => [
                '--verbose',
                '--log-path=' . $logPath,
            ],
        ];

        $client = Client::createChromeClient($chromeDriverBinary, null, $managerOptions);
        $crawler = $client->request('GET', $url);

        try {
            $client->waitFor('[data-testid="search-result-card"], [data-test="job-item"], li', 10);
        } catch (\Throwable) {
        }

        if ($debug) {
            $debugDir = $projectRoot . '/var/debug';
            $filesystem->mkdir($debugDir);
            file_put_contents($debugDir . '/wttj.html', $client->getPageSource());
            $client->takeScreenshot($debugDir . '/wttj.png');
        }

        $jobs = [];
        $count = 0;

        $jobs = array_merge($jobs, $this->parseJobCards($crawler, $limit, $count));

        if ($jobs === []) {
            $jobs = $this->parseJsonLd($crawler, $limit, $count);
        }

        foreach ($jobs as &$job) {
            $job['source'] = 'wttj';
        }
        unset($job);

        $client->quit();

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeWttjApi(string $query, ?int $limit = null, bool $debug = false): array
    {
        $appId = $_SERVER['WTTJ_ALGOLIA_APP_ID'] ?? '';
        $apiKey = $_SERVER['WTTJ_ALGOLIA_API_KEY'] ?? '';
        $index = $_SERVER['WTTJ_ALGOLIA_INDEX'] ?? 'wttj_jobs_production_fr';

        if ($appId === '' || $apiKey === '') {
            return [];
        }

        $hitsPerPage = 50;
        $page = 0;
        $jobs = [];
        $count = 0;

        do {
            $payload = [];
            $attempt = 0;

            while ($attempt < 3) {
                try {
                    $response = $this->httpClient->request('POST', sprintf('https://%s-dsn.algolia.net/1/indexes/%s/query', $appId, $index), [
                        'headers' => [
                            'X-Algolia-Application-Id' => $appId,
                            'X-Algolia-API-Key' => $apiKey,
                            'Referer' => self::WTTJ_API_REFERER,
                        ],
                        'json' => [
                            'query' => $query,
                            'hitsPerPage' => $hitsPerPage,
                            'page' => $page,
                        ],
                    ]);

                    $payload = $response->toArray(false);
                    break;
                } catch (\Throwable) {
                    $attempt++;
                    usleep($this->rateLimiter->getBackoffDelayMs($attempt) * 1000);
                }
            }

            if ($debug && $page === 0) {
                $projectRoot = getcwd() ?: '.';
                $filesystem = new Filesystem();
                $debugDir = $projectRoot . '/var/debug';
                $filesystem->mkdir($debugDir);
                file_put_contents($debugDir . '/wttj_algolia.json', json_encode($payload, JSON_PRETTY_PRINT));
            }

            $hits = $payload['hits'] ?? [];

            foreach ($hits as $hit) {
                if ($limit !== null && $count >= $limit) {
                    break 2;
                }

                if (!is_array($hit)) {
                    continue;
                }

                $title = trim((string) ($hit['name'] ?? $hit['title'] ?? ''));
                $organization = $hit['organization'] ?? [];
                $company = trim((string) (($organization['name'] ?? '') ?: ($hit['company_name'] ?? '')));
                $slug = trim((string) ($hit['slug'] ?? ''));
                $orgSlug = trim((string) ($organization['slug'] ?? ''));
                $jobUrl = $this->buildWttjJobUrl($orgSlug, $slug);
                $externalId = (string) ($hit['reference'] ?? $hit['objectID'] ?? '');

                if ($company === '' || $title === '') {
                    continue;
                }

                $externalId = $externalId !== '' ? $externalId : $this->buildExternalId($company, $title, $jobUrl);

                $jobs[] = [
                    'externalId' => $externalId,
                    'company' => $company,
                    'title' => $title,
                    'href' => $jobUrl,
                    'jobUrl' => $jobUrl,
                    'source' => 'wttj-api',
                ];

                $count++;
            }

            $page++;
        } while ($this->paginationPolicy->shouldContinue(count($hits), $hitsPerPage, null, $page));

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeRemotive(string $query, ?int $limit = null, bool $debug = false): array
    {
        $pageSize = 50;
        $page = 0;
        $jobs = [];
        $count = 0;
        $seenExternalIds = [];

        do {
            $url = self::REMOTIVE_URL . '?search=' . urlencode($query) . '&limit=' . $pageSize . '&page=' . $page;
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray(false);

            if ($debug && $page === 0) {
                $projectRoot = getcwd() ?: '.';
                $filesystem = new Filesystem();
                $debugDir = $projectRoot . '/var/debug';
                $filesystem->mkdir($debugDir);
                file_put_contents($debugDir . '/remotive.json', json_encode($data, JSON_PRETTY_PRINT));
            }

            $pageJobs = $data['jobs'] ?? [];
            $pageCount = 0;

            foreach ($pageJobs as $job) {
                if ($limit !== null && $count >= $limit) {
                    break 2;
                }

                $company = trim((string) ($job['company_name'] ?? ''));
                $title = trim((string) ($job['title'] ?? ''));
                $jobUrl = trim((string) ($job['url'] ?? ''));
                $externalId = (string) ($job['id'] ?? '');

                if ($company === '' || $title === '') {
                    continue;
                }

                $externalId = $externalId !== '' ? $externalId : $this->buildExternalId($company, $title, $jobUrl);

                if (isset($seenExternalIds[$externalId])) {
                    continue;
                }

                $seenExternalIds[$externalId] = true;
                $pageCount++;

                $jobs[] = [
                    'externalId' => $externalId,
                    'company' => $company,
                    'title' => $title,
                    'href' => $jobUrl,
                    'jobUrl' => $jobUrl,
                    'source' => 'remotive',
                ];

                $count++;
            }

            $page++;
        } while ($this->paginationPolicy->shouldContinue($pageCount, $pageSize, null, $page));

        return $jobs;
    }

    /**
     * @param string[] $sources
     * @param string[] $queries
     * @return array<int, array<string, string>>
     */
    public function scrapeSources(
        array $sources,
        array $queries,
        ?int $limit = null,
        bool $debug = false,
        ?string $fallbackUrl = null
    ): array {
        $sources = array_values(array_filter(array_map('strtolower', $sources)));
        $queries = array_values(array_filter($queries, static fn (string $query): bool => trim($query) !== ''));

        if ($sources === []) {
            $sources = ['wttj'];
        }

        if ($queries === []) {
            $queries = [''];
        }

        $results = [];

        foreach ($sources as $source) {
            foreach ($queries as $query) {
                if ($source === 'remotive') {
                    $results = array_merge($results, $this->scrapeRemotive($query, $limit, $debug));
                    continue;
                }

                if ($source === 'wttj-api') {
                    $results = array_merge($results, $this->scrapeWttjApi($query, $limit, $debug));
                    continue;
                }

                if ($source === 'wttj') {
                    $apiResults = $query !== '' ? $this->scrapeWttjApi($query, $limit, $debug) : [];
                    if ($apiResults !== []) {
                        $results = array_merge($results, $apiResults);
                        continue;
                    }

                    if ($fallbackUrl !== null && $fallbackUrl !== '') {
                        $results = array_merge($results, $this->scrapeWttj($fallbackUrl, $limit, $debug));
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseJobCards(Crawler $crawler, ?int $limit, int &$count): array
    {
        $jobs = [];
        $selectors = [
            '[data-testid="search-result-card"]',
            '[data-test="job-item"]',
            '[data-test-id="job-card"]',
            'li[class*="job"]',
            'li[class*="Job"]',
        ];

        $crawler->filter(implode(', ', $selectors))->each(function ($node) use (&$jobs, &$count, $limit) {
            if ($limit !== null && $count >= $limit) {
                return;
            }

            $companyNode = $node->filter('h3');
            $titleNode = $node->filter('h4, h2');
            $linkNode = $node->filter('a[href*="/jobs/"], a');

            if ($companyNode->count() === 0 || $titleNode->count() === 0 || $linkNode->count() === 0) {
                return;
            }

            $company = trim($companyNode->text());
            $title = trim($titleNode->text());
            $href = trim((string) $linkNode->attr('href'));
            $jobUrl = $this->normalizeUrl($href);

            if ($company === '' || $title === '') {
                return;
            }

            $externalId = $this->buildExternalId($company, $title, $jobUrl);

            $jobs[] = [
                'externalId' => $externalId,
                'company' => $company,
                'title' => $title,
                'href' => $href,
                'jobUrl' => $jobUrl,
            ];

            $count++;
        });

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseJsonLd(Crawler $crawler, ?int $limit, int &$count): array
    {
        $jobs = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function ($node) use (&$jobs, &$count, $limit) {
            if ($limit !== null && $count >= $limit) {
                return;
            }

            $json = trim($node->text());

            if ($json === '') {
                return;
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return;
            }

            $items = isset($decoded['@graph']) && is_array($decoded['@graph']) ? $decoded['@graph'] : [$decoded];

            foreach ($items as $item) {
                if ($limit !== null && $count >= $limit) {
                    break;
                }

                if (!is_array($item) || ($item['@type'] ?? '') !== 'JobPosting') {
                    continue;
                }

                $company = trim((string) ($item['hiringOrganization']['name'] ?? ''));
                $title = trim((string) ($item['title'] ?? ''));
                $jobUrl = trim((string) ($item['url'] ?? ''));

                if ($company === '' || $title === '') {
                    continue;
                }

                $externalId = $this->buildExternalId($company, $title, $jobUrl);

                $jobs[] = [
                    'externalId' => $externalId,
                    'company' => $company,
                    'title' => $title,
                    'href' => $jobUrl,
                    'jobUrl' => $jobUrl,
                ];

                $count++;
            }
        });

        return $jobs;
    }

    private function buildExternalId(string $company, string $title, string $jobUrl): string
    {
        $seed = trim($jobUrl) !== '' ? $jobUrl : $company . '|' . $title;

        return hash('sha256', $seed);
    }

    private function normalizeUrl(string $href): string
    {
        if ($href === '') {
            return '';
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            return self::BASE_URL . $href;
        }

        return self::BASE_URL . '/' . $href;
    }

    private function buildWttjJobUrl(string $orgSlug, string $jobSlug): string
    {
        if ($orgSlug !== '' && $jobSlug !== '') {
            return sprintf('%s/fr/companies/%s/jobs/%s', self::BASE_URL, $orgSlug, $jobSlug);
        }

        if ($jobSlug !== '') {
            return sprintf('%s/fr/jobs/%s', self::BASE_URL, $jobSlug);
        }

        return '';
    }
}
