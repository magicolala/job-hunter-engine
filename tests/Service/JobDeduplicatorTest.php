<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\JobDeduplicator;
use PHPUnit\Framework\TestCase;

class JobDeduplicatorTest extends TestCase
{
    public function testDeduplicateRemovesExistingAndDuplicates(): void
    {
        $deduplicator = new JobDeduplicator();

        $jobs = [
            ['externalId' => 'a1', 'company' => 'Acme', 'title' => 'Dev'],
            ['externalId' => 'a1', 'company' => 'Acme', 'title' => 'Dev'],
            ['externalId' => 'b2', 'company' => 'Beta', 'title' => 'Lead'],
        ];

        $result = $deduplicator->deduplicate($jobs, ['b2']);

        $this->assertCount(1, $result);
        $this->assertSame('a1', $result[0]['externalId']);
    }
}
