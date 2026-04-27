<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use App\Shared\Likes\LikeRepositoryInterface;

class LikeService
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository
    ) {}

    public function execute(Photo $photo, User $user): void
    {
        try {
            $this->likeRepository->createLike($photo, $user);
            $this->likeRepository->updatePhotoCounter($photo, 1);
        } catch (\Throwable $e) {
            throw new \Exception('Something went wrong while liking the photo');
        }
    }
}
