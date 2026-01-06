<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\JobHuntCommand;
use App\Repository\JobRepository;
use App\Service\CsvExporter;
use App\Service\JobScraper;
use App\Service\LeadFinder;
use App\Service\JobIngestionService;
use App\Service\ProfileCriteriaService;
use App\Service\JobDeduplicator;
use App\Service\ScrapingRunRecorder;
use App\Service\SourceRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class JobHuntCommandTest extends TestCase
{
    public function testCommandDefinesMultiSourceOptions(): void
    {
        $command = new JobHuntCommand(
            $this->createMock(JobScraper::class),
            $this->createMock(LeadFinder::class),
            $this->createMock(JobRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CsvExporter::class),
            $this->createMock(SourceRegistry::class),
            $this->createMock(JobDeduplicator::class),
            $this->createMock(ScrapingRunRecorder::class),
            $this->createMock(ProfileCriteriaService::class),
            $this->createMock(JobIngestionService::class)
        );

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('sources'));
        $this->assertTrue($definition->hasOption('queries'));
    }
}
