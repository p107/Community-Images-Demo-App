<?php
declare(strict_types=1);

namespace App\Shared\Photo;

interface PhotoRepositoryInterface
{
    public function findAllWithUsers(): array;
}
