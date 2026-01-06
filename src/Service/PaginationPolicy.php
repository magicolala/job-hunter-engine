<?php

declare(strict_types=1);

namespace App\Service;

final class PaginationPolicy
{
    public function shouldContinue(int $countOnPage, int $pageSize, ?int $maxPages, int $currentPage): bool
    {
        if ($maxPages !== null && $currentPage >= $maxPages) {
            return false;
        }

        return $countOnPage >= $pageSize;
    }
}
