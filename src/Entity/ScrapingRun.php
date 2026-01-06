<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ScrapingRunRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScrapingRunRepository::class)]
class ScrapingRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $sources = [];

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $queries = [];

    #[ORM\Column]
    private int $totalListings = 0;

    #[ORM\Column]
    private int $uniqueListings = 0;

    #[ORM\Column]
    private int $newJobs = 0;

    #[ORM\Column]
    private int $duplicates = 0;

    #[ORM\Column(length: 16)]
    private string $status = 'RUNNING';

    #[ORM\Column]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param string[] $sources
     */
    public function setSources(array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * @param string[] $queries
     */
    public function setQueries(array $queries): self
    {
        $this->queries = $queries;

        return $this;
    }

    public function getTotalListings(): int
    {
        return $this->totalListings;
    }

    public function setTotalListings(int $totalListings): self
    {
        $this->totalListings = $totalListings;

        return $this;
    }

    public function getUniqueListings(): int
    {
        return $this->uniqueListings;
    }

    public function setUniqueListings(int $uniqueListings): self
    {
        $this->uniqueListings = $uniqueListings;

        return $this;
    }

    public function getNewJobs(): int
    {
        return $this->newJobs;
    }

    public function setNewJobs(int $newJobs): self
    {
        $this->newJobs = $newJobs;

        return $this;
    }

    public function getDuplicates(): int
    {
        return $this->duplicates;
    }

    public function setDuplicates(int $duplicates): self
    {
        $this->duplicates = $duplicates;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
