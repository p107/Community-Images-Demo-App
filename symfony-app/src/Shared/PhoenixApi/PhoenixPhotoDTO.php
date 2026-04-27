<?php

declare(strict_types=1);

namespace App\Shared\PhoenixApi;

final class PhoenixPhotoDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $photoUrl,
        public readonly ?string $location,
        public readonly ?string $description,
        public readonly ?string $camera,
        public readonly ?\DateTimeImmutable $takenAt,
    ) {}
}
