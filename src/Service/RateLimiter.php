<?php

declare(strict_types=1);

namespace App\Service;

final class RateLimiter
{
    public function getBackoffDelayMs(int $attempt): int
    {
        $attempt = max(1, $attempt);
        $delay = 500 * (2 ** ($attempt - 1));

        return min(10000, $delay);
    }
}
