<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PhoenixApiClient;
use App\Shared\PhoenixApi\InvalidTokenException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PhoenixApiClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private PhoenixApiClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->client = new PhoenixApiClient($this->httpClient, 'http://phoenix:4000');
    }

    public function testFetchPhotosReturnsFullDTOs(): void
    {
        $listResponse = $this->createMock(ResponseInterface::class);
        $listResponse->method('getStatusCode')->willReturn(200);
        $listResponse->method('toArray')->willReturn([
            'photos' => [['id' => 1, 'photo_url' => 'https://example.com/1.jpg']],
        ]);

        $detailResponse = $this->createMock(ResponseInterface::class);
        $detailResponse->method('getStatusCode')->willReturn(200);
        $detailResponse->method('toArray')->willReturn([
            'photo' => [
                'id' => 1,
                'photo_url' => 'https://example.com/1.jpg',
                'location' => 'Rocky Mountains',
                'description' => 'Beautiful sunset',
                'camera' => 'Canon EOS R5',
                'taken_at' => '2024-06-15T06:30:00Z',
            ],
        ]);

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($listResponse, $detailResponse);

        $photos = $this->client->fetchPhotos('valid_token');

        $this->assertCount(1, $photos);
        $this->assertSame(1, $photos[0]->id);
        $this->assertSame('https://example.com/1.jpg', $photos[0]->photoUrl);
        $this->assertSame('Rocky Mountains', $photos[0]->location);
        $this->assertSame('Beautiful sunset', $photos[0]->description);
        $this->assertSame('Canon EOS R5', $photos[0]->camera);
        $this->assertInstanceOf(\DateTimeImmutable::class, $photos[0]->takenAt);
    }

    public function testFetchPhotosThrowsInvalidTokenExceptionOn401(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(401);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(InvalidTokenException::class);

        $this->client->fetchPhotos('invalid_token');
    }
}

