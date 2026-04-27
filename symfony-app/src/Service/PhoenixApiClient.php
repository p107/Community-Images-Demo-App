<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\PhoenixApi\InvalidTokenException;
use App\Shared\PhoenixApi\PhoenixPhotoDTO;
use App\Shared\PhoenixApi\RateLimitExceededException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhoenixApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $phoenixBaseUrl,
    ) {}

    /**
     * @return PhoenixPhotoDTO[]
     * @throws InvalidTokenException
     */
    public function fetchPhotos(string $token): array
    {
        $response = $this->httpClient->request('GET', $this->phoenixBaseUrl . '/api/photos', [
            'headers' => ['access-token' => $token],
        ]);

        if ($response->getStatusCode() === 401) {
            throw InvalidTokenException::create();
        }

        if ($response->getStatusCode() === 429) {
            throw RateLimitExceededException::create();
        }

        $list = $response->toArray()['photos'] ?? [];

        return array_map(
            fn(array $item) => $this->fetchPhoto($item['id'], $token),
            $list,
        );
    }

    /**
     * @throws InvalidTokenException
     */
    private function fetchPhoto(int $id, string $token): PhoenixPhotoDTO
    {
        $response = $this->httpClient->request('GET', $this->phoenixBaseUrl . '/api/photos/' . $id, [
            'headers' => ['access-token' => $token],
        ]);

        if ($response->getStatusCode() === 401) {
            throw InvalidTokenException::create();
        }

        if ($response->getStatusCode() === 429) {
            throw RateLimitExceededException::create();
        }

        $data = $response->toArray()['photo'];

        return new PhoenixPhotoDTO(
            id: $data['id'],
            photoUrl: $data['photo_url'],
            location: $data['location'] ?? null,
            description: $data['description'] ?? null,
            camera: $data['camera'] ?? null,
            takenAt: isset($data['taken_at'])
                ? new \DateTimeImmutable($data['taken_at'])
                : null,
        );
    }
}
