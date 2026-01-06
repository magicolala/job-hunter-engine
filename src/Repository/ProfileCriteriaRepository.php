<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProfileCriteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfileCriteria>
 */
class ProfileCriteriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfileCriteria::class);
    }
}
