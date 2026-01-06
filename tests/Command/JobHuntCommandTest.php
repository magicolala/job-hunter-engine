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
use App\Service\ScoringService;
use App\Service\TechnologyDictionary;
use App\Service\TechnologyExtractor;
use App\Repository\ProfileCriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class JobHuntCommandTest extends TestCase
{
    public function testCommandDefinesMultiSourceOptions(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $profileRepository = $this->createMock(ProfileCriteriaRepository::class);
        $profileCriteriaService = new ProfileCriteriaService($profileRepository, $entityManager);
        $scoringService = new ScoringService();
        $technologyExtractor = new TechnologyExtractor(new TechnologyDictionary());
        $ingestionService = new JobIngestionService($technologyExtractor, $scoringService);

        $command = new JobHuntCommand(
            $this->createMock(JobScraper::class),
            $this->createMock(LeadFinder::class),
            $this->createMock(JobRepository::class),
            $entityManager,
            $this->createMock(CsvExporter::class),
            new SourceRegistry(),
            new JobDeduplicator(),
            new ScrapingRunRecorder($entityManager),
            $profileCriteriaService,
            $ingestionService
        );

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('sources'));
        $this->assertTrue($definition->hasOption('queries'));
    }
}
