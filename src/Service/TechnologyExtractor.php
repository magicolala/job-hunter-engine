<?php

declare(strict_types=1);

namespace App\Service;

final class TechnologyExtractor
{
    public function __construct(private TechnologyDictionary $dictionary)
    {
    }

    /**
     * @return string[]
     */
    public function extract(string $text): array
    {
        $found = [];
        $haystack = strtolower($text);

        foreach ($this->dictionary->getAll() as $technology) {
            $needle = strtolower($technology);
            if ($needle === '') {
                continue;
            }

            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $haystack) === 1) {
                $found[$needle] = true;
            }
        }

        return array_keys($found);
    }
}
