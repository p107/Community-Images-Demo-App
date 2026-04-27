<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\PhoenixApi\InvalidTokenException;
use App\Shared\PhoenixApi\PhoenixPhotoDTO;
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
            'headers' => [
                'access-token' => $token,
            ],
        ]);

        if ($response->getStatusCode() === 401) {
            throw InvalidTokenException::create();
        }

        $data = $response->toArray();

        return array_map(
            static fn(array $photo) => new PhoenixPhotoDTO(
                id: $photo['id'],
                photoUrl: $photo['photo_url'],
            ),
            $data['photos'] ?? [],
        );
    }
}

