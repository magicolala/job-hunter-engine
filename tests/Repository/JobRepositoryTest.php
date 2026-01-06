<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class JobRepositoryTest extends TestCase
{
    public function testFindByFiltersAppliesParameters(): void
    {
        $repo = new TestJobRepository($this->createMock(ManagerRegistry::class));
        $publishedSince = new DateTimeImmutable('2026-01-01');

        $result = $repo->findByFilters(['symfony'], $publishedSince, ApplicationStatus::FOUND, 70);

        $this->assertSame([], $result);
        $this->assertSame('%"symfony"%', $repo->builder->params['tech0'] ?? null);
        $this->assertSame($publishedSince, $repo->builder->params['publishedSince'] ?? null);
        $this->assertSame(ApplicationStatus::FOUND, $repo->builder->params['status'] ?? null);
        $this->assertSame(70, $repo->builder->params['minScore'] ?? null);
    }
}

final class TestJobRepository extends JobRepository
{
    public FakeQueryBuilder $builder;

    public function createQueryBuilder($alias, $indexBy = null): FakeQueryBuilder
    {
        $this->builder = new FakeQueryBuilder();

        return $this->builder;
    }
}

final class FakeQueryBuilder
{
    public array $params = [];

    public function andWhere(string $expr): self
    {
        return $this;
    }

    public function setParameter(string $key, mixed $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function getQuery(): FakeQuery
    {
        return new FakeQuery();
    }
}

final class FakeQuery
{
    public function getResult(): array
    {
        return [];
    }
}
