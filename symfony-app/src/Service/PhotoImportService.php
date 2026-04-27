<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use App\Repository\ExternalApiTokenRepository;
use App\Shared\PhoenixApi\InvalidTokenException;
use Doctrine\ORM\EntityManagerInterface;

class PhotoImportService
{
    private const SERVICE_NAME = 'phoenix_api';

    public function __construct(
        private readonly ExternalApiTokenRepository $externalApiTokenRepository,
        private readonly PhoenixApiClient $phoenixApiClient,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return int Number of newly imported photos
     * @throws InvalidTokenException
     * @throws \RuntimeException When no token is configured for the user
     */
    public function importForUser(User $user): int
    {
        $externalToken = $this->externalApiTokenRepository->findByUserAndService($user, self::SERVICE_NAME);

        if (!$externalToken) {
            throw new \RuntimeException('No PhoenixApi token configured for this user.');
        }

        $photoDTOs = $this->phoenixApiClient->fetchPhotos($externalToken->getToken());

        $imported = 0;

        foreach ($photoDTOs as $dto) {
            if ($this->photoAlreadyExists($dto->photoUrl)) {
                continue;
            }

            $photo = (new Photo())
                ->setImageUrl($dto->photoUrl)
                ->setLocation($dto->location)
                ->setDescription($dto->description)
                ->setCamera($dto->camera)
                ->setTakenAt($dto->takenAt)
                ->setUser($user);

            $this->em->persist($photo);
            $imported++;
        }

        if ($imported > 0) {
            $this->em->flush();
        }

        return $imported;
    }

    private function photoAlreadyExists(string $imageUrl): bool
    {
        return $this->em->getRepository(Photo::class)
            ->findOneBy(['imageUrl' => $imageUrl]) !== null;
    }
}

