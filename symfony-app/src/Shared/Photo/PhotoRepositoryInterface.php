<?php
declare(strict_types=1);

namespace App\Shared\Photo;

use App\DTO\PhotoFilterDTO;

interface PhotoRepositoryInterface
{
    public function findAllWithUsers(): array;

    /** @return \App\Entity\Photo[] */
    public function findWithFilters(PhotoFilterDTO $filter): array;
}
