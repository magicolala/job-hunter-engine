<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CsvExporter;
use PHPUnit\Framework\TestCase;

class CsvExporterTest extends TestCase
{
    public function testExportCreatesCsvWithHeaderAndRows(): void
    {
        $exporter = new CsvExporter();
        $dir = sys_get_temp_dir() . '/job-hunter-' . uniqid('', true);
        $path = $dir . '/export.csv';

        $rows = [
            ['https://linkedin.com/in/alice', 'Alice', 'Doe', 'Acme', 'CTO'],
            ['https://linkedin.com/in/bob', 'Bob', 'Smith', 'Beta', 'Lead Dev'],
        ];

        $result = $exporter->export($rows, $path);

        $this->assertSame($path, $result);
        $this->assertFileExists($path);

        $contents = file($path, FILE_IGNORE_NEW_LINES);

        $this->assertSame('linkedin_url,first_name,last_name,company,job_title', $contents[0]);
        $this->assertSame('https://linkedin.com/in/alice,Alice,Doe,Acme,CTO', $contents[1]);
        $this->assertSame('https://linkedin.com/in/bob,Bob,Smith,Beta,"Lead Dev"', $contents[2]);

        unlink($path);
        rmdir($dir);
    }
}
