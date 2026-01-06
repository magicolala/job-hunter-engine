<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PaginationPolicy;
use PHPUnit\Framework\TestCase;

class PaginationPolicyTest extends TestCase
{
    public function testStopsWhenPageNotFull(): void
    {
        $policy = new PaginationPolicy();

        $this->assertFalse($policy->shouldContinue(10, 50, null, 1));
    }

    public function testStopsWhenMaxPagesReached(): void
    {
        $policy = new PaginationPolicy();

        $this->assertFalse($policy->shouldContinue(50, 50, 2, 2));
    }

    public function testContinuesWhenFullAndBelowMax(): void
    {
        $policy = new PaginationPolicy();

        $this->assertTrue($policy->shouldContinue(50, 50, 5, 2));
    }
}
