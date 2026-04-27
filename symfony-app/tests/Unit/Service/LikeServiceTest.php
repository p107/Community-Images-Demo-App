<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Like;
use App\Entity\Photo;
use App\Entity\User;
use App\Service\LikeService;
use App\Shared\Likes\LikeException;
use App\Shared\Likes\LikeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LikeServiceTest extends TestCase
{
    private LikeRepositoryInterface&MockObject $likeRepository;
    private LikeService $likeService;

    protected function setUp(): void
    {
        $this->likeRepository = $this->createMock(LikeRepositoryInterface::class);
        $this->likeService = new LikeService($this->likeRepository);
    }

    public function testExecuteCreatesLikeAndUpdatesCounter(): void
    {
        $photo = $this->createStub(Photo::class);
        $user = $this->createStub(User::class);
        $like = $this->createStub(Like::class);

        $this->likeRepository
            ->expects($this->once())
            ->method('createLike')
            ->with($photo, $user)
            ->willReturn($like);

        $this->likeRepository
            ->expects($this->once())
            ->method('updatePhotoCounter')
            ->with($photo, 1);

        $this->likeService->execute($photo, $user);
    }

    public function testExecuteThrowsLikeExceptionWhenRepositoryFails(): void
    {
        $photo = $this->createStub(Photo::class);
        $user = $this->createStub(User::class);
        $originalException = new \RuntimeException('DB connection lost');

        $this->likeRepository
            ->method('createLike')
            ->willThrowException($originalException);

        $this->expectException(LikeException::class);
        $this->expectExceptionMessage('Something went wrong while liking the photo.');

        $this->likeService->execute($photo, $user);
    }

    public function testExecutePreservesOriginalExceptionAsPrevious(): void
    {
        $photo = $this->createStub(Photo::class);
        $user = $this->createStub(User::class);
        $originalException = new \RuntimeException('DB connection lost');

        $this->likeRepository
            ->method('createLike')
            ->willThrowException($originalException);

        try {
            $this->likeService->execute($photo, $user);
            $this->fail('LikeException was not thrown');
        } catch (LikeException $e) {
            $this->assertSame($originalException, $e->getPrevious());
        }
    }
}

