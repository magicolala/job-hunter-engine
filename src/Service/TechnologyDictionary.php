<?php

declare(strict_types=1);

namespace App\Service;

final class TechnologyDictionary
{
    /**
     * @return string[]
     */
    public function getAll(): array
    {
        return [
            'php',
            'symfony',
            'laravel',
            'react',
            'vue',
            'angular',
            'docker',
            'kubernetes',
            'postgresql',
            'mysql',
            'redis',
        ];
    }
}
