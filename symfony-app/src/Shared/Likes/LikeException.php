<?php

declare(strict_types=1);

namespace App\Shared\Likes;

final class LikeException extends \RuntimeException
{
    public static function whileLiking(\Throwable $previous): self
    {
        return new self('Something went wrong while liking the photo.', 0, $previous);
    }
}

