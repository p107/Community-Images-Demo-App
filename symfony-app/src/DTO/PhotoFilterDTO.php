<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

final class PhotoFilterDTO
{
    public function __construct(
        public readonly ?string $location,
        public readonly ?string $camera,
        public readonly ?string $description,
        public readonly ?\DateTimeImmutable $takenAtFrom,
        public readonly ?\DateTimeImmutable $takenAtTo,
        public readonly ?string $username,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $query = $request->query;

        return new self(
            location: self::nullIfEmpty($query->get('location')),
            camera: self::nullIfEmpty($query->get('camera')),
            description: self::nullIfEmpty($query->get('description')),
            takenAtFrom: self::parseDate($query->get('taken_at_from')),
            takenAtTo: self::parseDate($query->get('taken_at_to')),
            username: self::nullIfEmpty($query->get('username')),
        );
    }

    public function isEmpty(): bool
    {
        return $this->location === null
            && $this->camera === null
            && $this->description === null
            && $this->takenAtFrom === null
            && $this->takenAtTo === null
            && $this->username === null;
    }

    private static function nullIfEmpty(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        return trim($value);
    }

    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($value));

        return $date !== false ? $date : null;
    }
}

