<?php

declare(strict_types=1);

namespace App\Service;

final class SourceRegistry
{
    public const SOURCE_WTTJ = 'wttj';
    public const SOURCE_WTTJ_API = 'wttj-api';
    public const SOURCE_REMOTIVE = 'remotive';

    /**
     * @return string[]
     */
    public function getSupportedSources(): array
    {
        return [
            self::SOURCE_WTTJ,
            self::SOURCE_WTTJ_API,
            self::SOURCE_REMOTIVE,
        ];
    }

    /**
     * @param string[] $sources
     * @return string[]
     */
    public function normalizeSources(array $sources): array
    {
        $normalized = array_values(array_filter(array_map(
            static fn (string $source): string => strtolower(trim($source)),
            $sources
        )));

        return $normalized !== [] ? $normalized : [self::SOURCE_WTTJ];
    }

    public function isSupported(string $source): bool
    {
        return in_array($source, $this->getSupportedSources(), true);
    }
}
