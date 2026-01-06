<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Enum\ApplicationStatus;
use App\Repository\JobRepository;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
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
        $this->assertSame('%\\"symfony\\"%', $repo->builder->params['tech0'] ?? null);
        $this->assertSame($publishedSince, $repo->builder->params['publishedSince'] ?? null);
        $this->assertSame(ApplicationStatus::FOUND, $repo->builder->params['status'] ?? null);
        $this->assertSame(70, $repo->builder->params['minScore'] ?? null);
    }
}

final class TestJobRepository extends JobRepository
{
    public FakeQueryBuilder $builder;

    public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
    {
        $this->builder = new FakeQueryBuilder();

        return $this->builder;
    }
}

final class FakeQueryBuilder extends QueryBuilder
{
    public array $params = [];

    public function __construct()
    {
    }

    public function andWhere(mixed ...$where): static
    {
        return $this;
    }

    public function setParameter(
        string|int $key,
        mixed $value,
        ParameterType|ArrayParameterType|string|int|null $type = null
    ): static
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function getQuery(): Query
    {
        return new FakeQuery();
    }
}

final class FakeQuery extends Query
{
    public function __construct()
    {
    }

    public function getResult(string|int $hydrationMode = self::HYDRATE_OBJECT): mixed
    {
        return [];
    }
}
