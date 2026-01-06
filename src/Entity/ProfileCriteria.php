<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProfileCriteriaRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileCriteriaRepository::class)]
class ProfileCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $preferredTechnologies = [];

    #[ORM\Column(length: 32)]
    private string $seniority;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $locations = [];

    #[ORM\Column]
    private bool $remoteOnly = false;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getPreferredTechnologies(): array
    {
        return $this->preferredTechnologies;
    }

    /**
     * @param string[] $preferredTechnologies
     */
    public function setPreferredTechnologies(array $preferredTechnologies): self
    {
        $this->preferredTechnologies = $preferredTechnologies;

        return $this;
    }

    public function getSeniority(): string
    {
        return $this->seniority;
    }

    public function setSeniority(string $seniority): self
    {
        $this->seniority = $seniority;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param string[] $locations
     */
    public function setLocations(array $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    public function isRemoteOnly(): bool
    {
        return $this->remoteOnly;
    }

    public function setRemoteOnly(bool $remoteOnly): self
    {
        $this->remoteOnly = $remoteOnly;

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
