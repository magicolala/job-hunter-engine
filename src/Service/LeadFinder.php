<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LeadFinder
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(APOLLO_API_KEY)%')]
        private string $apolloApiKey
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function findCTO(string $companyDomain): array
    {
        if ($this->apolloApiKey === '') {
            return [];
        }

        try {
            $response = $this->client->request('POST', 'https://api.apollo.io/v1/people/search', [
                'json' => [
                    'api_key' => $this->apolloApiKey,
                    'q_organization_domains' => $companyDomain,
                    'person_titles' => ['CTO', 'Lead Developer', 'Engineering Manager'],
                ],
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        return $data['people'][0] ?? [];
    }

    public function isEnabled(): bool
    {
        return $this->apolloApiKey !== '';
    }
}
