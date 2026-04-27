<?php

declare(strict_types=1);

namespace App\Shared\PhoenixApi;

final class InvalidTokenException extends \RuntimeException
{
    public static function create(): self
    {
        return new self('PhoenixApi token is invalid or has been revoked.');
    }
}

