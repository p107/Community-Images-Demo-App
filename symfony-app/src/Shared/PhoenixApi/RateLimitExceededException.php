<?php

declare(strict_types=1);

namespace App\Shared\PhoenixApi;

final class RateLimitExceededException extends \RuntimeException
{
    public static function create(): self
    {
        return new self('PhoenixApi rate limit exceeded. Try again later.');
    }
}

