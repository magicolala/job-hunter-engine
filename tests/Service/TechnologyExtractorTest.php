<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TechnologyDictionary;
use App\Service\TechnologyExtractor;
use PHPUnit\Framework\TestCase;

class TechnologyExtractorTest extends TestCase
{
    public function testExtractsKnownTechnologies(): void
    {
        $dictionary = new TechnologyDictionary();
        $extractor = new TechnologyExtractor($dictionary);

        $text = 'We use Symfony, Docker, and PostgreSQL in our stack.';
        $result = $extractor->extract($text);

        $this->assertContains('symfony', $result);
        $this->assertContains('docker', $result);
        $this->assertContains('postgresql', $result);
    }
}
