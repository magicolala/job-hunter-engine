<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ProfileCriteria;
use App\Repository\ProfileCriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileCriteriaService
{
    public function __construct(
        private ProfileCriteriaRepository $repository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getCurrent(): ProfileCriteria
    {
        $criteria = $this->repository->findOneBy([]);

        if ($criteria instanceof ProfileCriteria) {
            return $criteria;
        }

        $criteria = new ProfileCriteria();
        $criteria->setSeniority('mid');
        $criteria->setPreferredTechnologies([]);
        $criteria->setLocations([]);
        $criteria->setRemoteOnly(false);

        $this->entityManager->persist($criteria);
        $this->entityManager->flush();

        return $criteria;
    }
}
