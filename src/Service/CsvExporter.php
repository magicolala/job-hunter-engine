<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class CsvExporter
{
    /**
     * @param array<int, array<int, string>> $rows
     */
    public function export(array $rows, string $path): string
    {
        $filesystem = new Filesystem();
        $directory = dirname($path);

        if ($directory !== '' && $directory !== '.') {
            $filesystem->mkdir($directory);
        }

        $handle = fopen($path, 'w');

        if ($handle === false) {
            return $path;
        }

        fputcsv($handle, ['linkedin_url', 'first_name', 'last_name', 'company', 'job_title']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }
}
