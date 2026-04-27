<?php
declare(strict_types=1);

namespace App\Shared\Likes;

use App\Entity\Like;
use App\Entity\Photo;
use App\Entity\User;

interface LikeRepositoryInterface
{
    public function unlikePhoto(Photo $photo, User $user): void;

    public function hasUserLikedPhoto(Photo $photo, User $user): bool;

    public function createLike(Photo $photo, User $user): Like;

    public function updatePhotoCounter(Photo $photo, int $increment): void;
}