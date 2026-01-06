<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
class Job
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 64)]
    private string $source;

    #[ORM\Column(length: 255)]
    private string $company;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $technologies = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $relevanceScore = null;

    #[ORM\Column(enumType: ApplicationStatus::class)]
    private ApplicationStatus $status;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $statusUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactLinkedin = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $jobUrl = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->status = ApplicationStatus::FOUND;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTechnologies(): array
    {
        return $this->technologies;
    }

    /**
     * @param string[] $technologies
     */
    public function setTechnologies(array $technologies): self
    {
        $this->technologies = $technologies;

        return $this;
    }

    public function getRelevanceScore(): ?int
    {
        return $this->relevanceScore;
    }

    public function setRelevanceScore(?int $relevanceScore): self
    {
        $this->relevanceScore = $relevanceScore;

        return $this;
    }

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusUpdatedAt(): ?DateTimeImmutable
    {
        return $this->statusUpdatedAt;
    }

    public function setStatusUpdatedAt(?DateTimeImmutable $statusUpdatedAt): self
    {
        $this->statusUpdatedAt = $statusUpdatedAt;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactLinkedin(): ?string
    {
        return $this->contactLinkedin;
    }

    public function setContactLinkedin(?string $contactLinkedin): self
    {
        $this->contactLinkedin = $contactLinkedin;

        return $this;
    }

    public function getJobUrl(): ?string
    {
        return $this->jobUrl;
    }

    public function setJobUrl(?string $jobUrl): self
    {
        $this->jobUrl = $jobUrl;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
