<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\ApplicationStatus;
use App\Entity\Job;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @param string[] $externalIds
     * @return string[]
     */
    public function findExistingExternalIds(array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('job')
            ->select('job.externalId')
            ->where('job.externalId IN (:externalIds)')
            ->setParameter('externalIds', $externalIds)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_map(
            static fn (array $row): ?string => $row['externalId'] ?? null,
            $rows
        )));
    }

    /**
     * @param string[] $technologies
     * @return Job[]
     */
    public function findByFilters(
        array $technologies = [],
        ?DateTimeImmutable $publishedSince = null,
        ?ApplicationStatus $status = null,
        ?int $minScore = null
    ): array {
        $qb = $this->createQueryBuilder('job');

        if ($technologies !== []) {
            foreach ($technologies as $index => $technology) {
                $param = 'tech' . $index;
                $qb->andWhere('job.technologies LIKE :' . $param)
                    ->setParameter($param, '%\"' . $technology . '\"%');
            }
        }

        if ($publishedSince !== null) {
            $qb->andWhere('job.publishedAt >= :publishedSince')
                ->setParameter('publishedSince', $publishedSince);
        }

        if ($status !== null) {
            $qb->andWhere('job.status = :status')
                ->setParameter('status', $status);
        }

        if ($minScore !== null) {
            $qb->andWhere('job.relevanceScore >= :minScore')
                ->setParameter('minScore', $minScore);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array{status: string, count: int}>
     */
    public function getPipelineSummary(): array
    {
        $rows = $this->createQueryBuilder('job')
            ->select('job.status AS status, COUNT(job.id) AS count')
            ->groupBy('job.status')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $row): array {
                $status = $row['status'] ?? '';
                if ($status instanceof \BackedEnum) {
                    $status = $status->value;
                }

                return [
                    'status' => (string) $status,
                    'count' => (int) ($row['count'] ?? 0),
                ];
            },
            $rows
        );
    }
}
