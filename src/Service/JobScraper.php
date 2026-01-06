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
    private const DEFAULT_ADZUNA_COUNTRY = 'fr';
    private const BRIGHTDATA_API_BASE = 'https://api.brightdata.com/datasets/v3';

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

                $description = trim((string) ($hit['description'] ?? $hit['mission'] ?? ''));
                $location = '';
                if (isset($hit['office']) && is_array($hit['office'])) {
                    $location = trim((string) ($hit['office']['city'] ?? $hit['office']['name'] ?? ''));
                }
                if ($location === '' && isset($hit['locations']) && is_array($hit['locations'])) {
                    $firstLocation = $hit['locations'][0] ?? [];
                    if (is_array($firstLocation)) {
                        $location = trim((string) ($firstLocation['city'] ?? $firstLocation['name'] ?? ''));
                    }
                }
                $publishedAt = (string) ($hit['published_at'] ?? $hit['created_at'] ?? '');

                $jobs[] = [
                    'externalId' => $externalId,
                    'company' => $company,
                    'title' => $title,
                    'href' => $jobUrl,
                    'jobUrl' => $jobUrl,
                    'source' => 'wttj-api',
                    'description' => $description,
                    'location' => $location,
                    'publishedAt' => $publishedAt,
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
            $description = trim((string) ($job['description'] ?? ''));
            $location = trim((string) ($job['candidate_required_location'] ?? ''));
            $publishedAt = (string) ($job['publication_date'] ?? '');
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
                'description' => $description,
                'location' => $location,
                'publishedAt' => $publishedAt,
            ];

                $count++;
            }

            $page++;
        } while ($this->paginationPolicy->shouldContinue($pageCount, $pageSize, null, $page));

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeAdzuna(string $query, ?int $limit = null, bool $debug = false, ?string $region = null): array
    {
        $appId = $_SERVER['ADZUNA_APP_ID'] ?? '';
        $appKey = $_SERVER['ADZUNA_APP_KEY'] ?? '';
        $country = $_SERVER['ADZUNA_COUNTRY'] ?? self::DEFAULT_ADZUNA_COUNTRY;

        if ($appId === '' || $appKey === '') {
            return [];
        }

        $resultsPerPage = 50;
        $page = 1;
        $jobs = [];
        $count = 0;

        do {
            $params = [
                'app_id' => $appId,
                'app_key' => $appKey,
                'what' => $query,
                'results_per_page' => $resultsPerPage,
            ];
            if ($region !== null && $region !== '') {
                $params['where'] = $region;
            }

            $url = sprintf('https://api.adzuna.com/v1/api/jobs/%s/search/%d?%s', $country, $page, http_build_query($params));

            try {
                $response = $this->httpClient->request('GET', $url);
                $payload = $response->toArray(false);
            } catch (\Throwable) {
                break;
            }

            $results = $payload['results'] ?? [];
            $pageCount = 0;

            foreach ($results as $result) {
                if ($limit !== null && $count >= $limit) {
                    break 2;
                }

                if (!is_array($result)) {
                    continue;
                }

                $title = trim((string) ($result['title'] ?? ''));
                $company = trim((string) ($result['company']['display_name'] ?? ''));
                $jobUrl = trim((string) ($result['redirect_url'] ?? ''));
                $location = trim((string) ($result['location']['display_name'] ?? ''));
                $description = trim((string) ($result['description'] ?? ''));
                $publishedAt = (string) ($result['created'] ?? '');
                $externalId = (string) ($result['id'] ?? '');

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
                    'source' => 'adzuna',
                    'description' => $description,
                    'location' => $location,
                    'publishedAt' => $publishedAt,
                ];

                $count++;
                $pageCount++;
            }

            $page++;
        } while ($this->paginationPolicy->shouldContinue($pageCount, $resultsPerPage, null, $page));

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeHumanCoders(string $query, ?int $limit = null): array
    {
        $url = $_SERVER['HUMANCODERS_FEED_URL'] ?? 'https://www.humancoders.com/jobs.rss';

        return $this->scrapeRssFeed($url, $query, $limit, 'humancoders');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeAlsacreations(string $query, ?int $limit = null): array
    {
        $url = $_SERVER['ALSACREATIONS_FEED_URL'] ?? 'https://www.alsacreations.com/rss/jobs.xml';

        return $this->scrapeRssFeed($url, $query, $limit, 'alsacreations');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeLinkedin(string $query, ?int $limit = null): array
    {
        $apiUrl = $_SERVER['LINKEDIN_JOBS_API_URL'] ?? '';
        $apiKey = $_SERVER['LINKEDIN_JOBS_API_KEY'] ?? '';

        if ($apiUrl === '' || $apiKey === '') {
            return [];
        }

        return $this->scrapeJsonApi($apiUrl, $apiKey, $query, $limit, 'linkedin');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function scrapeIndeed(string $query, ?int $limit = null): array
    {
        $datasetId = $_SERVER['BRIGHTDATA_INDEED_DATASET_ID'] ?? '';
        $apiKey = $_SERVER['BRIGHTDATA_API_KEY'] ?? '';

        if ($datasetId !== '' && $apiKey !== '') {
            return $this->scrapeIndeedBrightData($query, $limit);
        }

        $apiUrl = $_SERVER['INDEED_API_URL'] ?? '';
        $fallbackKey = $_SERVER['INDEED_API_KEY'] ?? '';

        if ($apiUrl === '' || $fallbackKey === '') {
            return [];
        }

        return $this->scrapeJsonApi($apiUrl, $fallbackKey, $query, $limit, 'indeed');
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
        ?string $fallbackUrl = null,
        ?string $region = null
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

                if ($source === 'adzuna') {
                    $results = array_merge($results, $this->scrapeAdzuna($query, $limit, $debug, $region));
                    continue;
                }

                if ($source === 'humancoders') {
                    $results = array_merge($results, $this->scrapeHumanCoders($query, $limit));
                    continue;
                }

                if ($source === 'alsacreations') {
                    $results = array_merge($results, $this->scrapeAlsacreations($query, $limit));
                    continue;
                }

                if ($source === 'linkedin') {
                    $results = array_merge($results, $this->scrapeLinkedin($query, $limit));
                    continue;
                }

                if ($source === 'indeed') {
                    $results = array_merge($results, $this->scrapeIndeed($query, $limit));
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
    private function scrapeRssFeed(string $url, string $query, ?int $limit, string $source): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();
        } catch (\Throwable) {
            return [];
        }

        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            return [];
        }

        $items = [];
        if (isset($xml->channel->item)) {
            $items = $xml->channel->item;
        } elseif (isset($xml->entry)) {
            $items = $xml->entry;
        }

        $jobs = [];
        $count = 0;
        $needle = strtolower($query);

        foreach ($items as $item) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            $title = trim((string) ($item->title ?? ''));
            $description = trim((string) ($item->description ?? $item->summary ?? ''));
            $link = '';
            if (isset($item->link)) {
                $link = isset($item->link['href']) ? (string) $item->link['href'] : (string) $item->link;
            }
            $publishedAt = (string) ($item->pubDate ?? $item->published ?? '');

            if ($title === '') {
                continue;
            }

            if ($needle !== '' && stripos($title . ' ' . $description, $needle) === false) {
                continue;
            }

            $externalId = $this->buildExternalId($source, $title, $link);

            $jobs[] = [
                'externalId' => $externalId,
                'company' => $source,
                'title' => $title,
                'href' => $link,
                'jobUrl' => $link,
                'source' => $source,
                'description' => $description,
                'location' => '',
                'publishedAt' => $publishedAt,
            ];

            $count++;
        }

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function scrapeJsonApi(string $apiUrl, string $apiKey, string $query, ?int $limit, string $source): array
    {
        try {
            $response = $this->httpClient->request('GET', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'query' => [
                    'query' => $query,
                    'limit' => $limit ?? 50,
                ],
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        $items = $payload['jobs'] ?? $payload['results'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $jobs = [];
        $count = 0;

        foreach ($items as $item) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $company = trim((string) ($item['company'] ?? ''));
            $jobUrl = trim((string) ($item['url'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $location = trim((string) ($item['location'] ?? ''));
            $publishedAt = (string) ($item['published_at'] ?? $item['created_at'] ?? '');
            $externalId = (string) ($item['id'] ?? '');

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
                'source' => $source,
                'description' => $description,
                'location' => $location,
                'publishedAt' => $publishedAt,
            ];

            $count++;
        }

        return $jobs;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function scrapeIndeedBrightData(string $query, ?int $limit): array
    {
        $datasetId = $_SERVER['BRIGHTDATA_INDEED_DATASET_ID'] ?? '';
        $apiKey = $_SERVER['BRIGHTDATA_API_KEY'] ?? '';
        $country = $_SERVER['BRIGHTDATA_INDEED_COUNTRY'] ?? 'FR';
        $domain = $_SERVER['BRIGHTDATA_INDEED_DOMAIN'] ?? 'fr.indeed.com';
        $datePosted = $_SERVER['BRIGHTDATA_INDEED_DATE_POSTED'] ?? 'Last 7 days';
        $locationRadius = $_SERVER['BRIGHTDATA_INDEED_LOCATION_RADIUS'] ?? '';

        if ($datasetId === '' || $apiKey === '') {
            return [];
        }

        $payload = [
            'input' => [[
                'country' => $country,
                'domain' => $domain,
                'keyword_search' => $query,
                'location' => $_SERVER['BRIGHTDATA_INDEED_LOCATION'] ?? '',
                'date_posted' => $datePosted,
                'posted_by' => '',
                'location_radius' => $locationRadius,
            ]],
        ];

        $triggerUrl = sprintf(
            '%s/trigger?dataset_id=%s&notify=false&include_errors=true&type=discover_new&discover_by=keyword',
            self::BRIGHTDATA_API_BASE,
            urlencode($datasetId)
        );

        try {
            $response = $this->httpClient->request('POST', $triggerUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $payload = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        $items = $this->extractBrightDataItems($payload, $apiKey);

        if ($items === []) {
            return [];
        }

        $jobs = [];
        $count = 0;

        foreach ($items as $item) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['job_title'] ?? $item['title'] ?? ''));
            $company = trim((string) ($item['company_name'] ?? $item['company'] ?? ''));
            $jobUrl = trim((string) ($item['apply_link'] ?? $item['url'] ?? ''));
            $description = trim((string) ($item['description_text'] ?? $item['description'] ?? ''));
            $location = trim((string) ($item['location'] ?? $item['job_location'] ?? ''));
            $publishedAt = (string) ($item['date_posted_parsed'] ?? $item['date_posted'] ?? '');
            $externalId = (string) ($item['jobid'] ?? $item['id'] ?? '');

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
                'source' => 'indeed',
                'description' => $description,
                'location' => $location,
                'publishedAt' => $publishedAt,
            ];

            $count++;
        }

        return $jobs;
    }

    /**
     * @param array<mixed> $payload
     * @return array<int, mixed>
     */
    private function extractBrightDataItems(array $payload, string $apiKey): array
    {
        if (isset($payload[0]) && is_array($payload[0])) {
            return $payload;
        }

        $snapshotId = $payload['snapshot_id'] ?? $payload['snapshotId'] ?? $payload['id'] ?? null;
        if (!is_string($snapshotId) || $snapshotId === '') {
            return [];
        }

        $snapshotUrl = sprintf('%s/snapshot/%s?format=json', self::BRIGHTDATA_API_BASE, urlencode($snapshotId));

        try {
            $response = $this->httpClient->request('GET', $snapshotUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
            ]);
            $snapshot = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        if (isset($snapshot[0]) && is_array($snapshot[0])) {
            return $snapshot;
        }

        if (isset($snapshot['data']) && is_array($snapshot['data'])) {
            return $snapshot['data'];
        }

        return [];
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
