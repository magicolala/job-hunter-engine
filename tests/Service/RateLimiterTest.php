<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    public function testBackoffIncreasesWithAttempts(): void
    {
        $limiter = new RateLimiter();

        $this->assertSame(500, $limiter->getBackoffDelayMs(1));
        $this->assertSame(1000, $limiter->getBackoffDelayMs(2));
        $this->assertSame(2000, $limiter->getBackoffDelayMs(3));
    }
}
