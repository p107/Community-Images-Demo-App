<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ExternalApiToken;
use App\Entity\User;
use App\Repository\ExternalApiTokenRepository;
use App\Service\PhoenixApiClient;
use App\Service\PhotoImportService;
use App\Shared\PhoenixApi\InvalidTokenException;
use App\Shared\PhoenixApi\PhoenixPhotoDTO;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhotoImportServiceTest extends TestCase
{
    private ExternalApiTokenRepository&MockObject $tokenRepository;
    private PhoenixApiClient&MockObject $phoenixClient;
    private EntityManagerInterface&MockObject $em;
    private PhotoImportService $service;

    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(ExternalApiTokenRepository::class);
        $this->phoenixClient = $this->createMock(PhoenixApiClient::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new PhotoImportService(
            $this->tokenRepository,
            $this->phoenixClient,
            $this->em,
        );
    }

    public function testImportForUserImportsNewPhotosAndReturnsCount(): void
    {
        $user = $this->createStub(User::class);

        $externalToken = $this->createStub(ExternalApiToken::class);
        $externalToken->method('getToken')->willReturn('valid_token');

        $this->tokenRepository
            ->method('findByUserAndService')
            ->with($user, 'phoenix_api')
            ->willReturn($externalToken);

        $dto = new PhoenixPhotoDTO(
            id: 1,
            photoUrl: 'https://example.com/1.jpg',
            location: 'Rocky Mountains',
            description: 'Sunset',
            camera: 'Canon EOS R5',
            takenAt: new \DateTimeImmutable('2024-06-15T06:30:00Z'),
        );

        $this->phoenixClient
            ->method('fetchPhotos')
            ->with('valid_token')
            ->willReturn([$dto]);

        // No duplicate — repository returns null
        $photoRepo = $this->createMock(EntityRepository::class);
        $photoRepo->method('findOneBy')->willReturn(null);
        $this->em->method('getRepository')->willReturn($photoRepo);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $imported = $this->service->importForUser($user);

        $this->assertSame(1, $imported);
    }

    public function testImportForUserSkipsDuplicates(): void
    {
        $user = $this->createStub(User::class);

        $externalToken = $this->createStub(ExternalApiToken::class);
        $externalToken->method('getToken')->willReturn('valid_token');

        $this->tokenRepository
            ->method('findByUserAndService')
            ->willReturn($externalToken);

        $dto = new PhoenixPhotoDTO(
            id: 1,
            photoUrl: 'https://example.com/1.jpg',
            location: null,
            description: null,
            camera: null,
            takenAt: null,
        );

        $this->phoenixClient->method('fetchPhotos')->willReturn([$dto]);

        // Photo already exists
        $existingPhoto = $this->createStub(\App\Entity\Photo::class);
        $photoRepo = $this->createMock(EntityRepository::class);
        $photoRepo->method('findOneBy')->willReturn($existingPhoto);
        $this->em->method('getRepository')->willReturn($photoRepo);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $imported = $this->service->importForUser($user);

        $this->assertSame(0, $imported);
    }

    public function testImportForUserThrowsWhenNoTokenConfigured(): void
    {
        $user = $this->createStub(User::class);

        $this->tokenRepository
            ->method('findByUserAndService')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->importForUser($user);
    }

    public function testImportForUserPropagatesInvalidTokenException(): void
    {
        $user = $this->createStub(User::class);

        $externalToken = $this->createStub(ExternalApiToken::class);
        $externalToken->method('getToken')->willReturn('bad_token');

        $this->tokenRepository
            ->method('findByUserAndService')
            ->willReturn($externalToken);

        $this->phoenixClient
            ->method('fetchPhotos')
            ->willThrowException(InvalidTokenException::create());

        $this->expectException(InvalidTokenException::class);

        $this->service->importForUser($user);
    }
}

