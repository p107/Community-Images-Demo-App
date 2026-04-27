<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DTO\PhotoFilterDTO;
use App\Entity\Photo;
use App\Entity\User;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for PhotoRepository::findWithFilters().
 * Each test runs in a transaction that is rolled back after the test,
 * so the database is always clean.
 */
class PhotoRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PhotoRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = self::$kernel->getContainer();
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->repo = $this->em->getRepository(\App\Entity\Photo::class);

        // Clean up test data before each test
        $conn = $this->em->getConnection();
        $conn->executeStatement('DELETE FROM likes');
        $conn->executeStatement('DELETE FROM photos');
        $conn->executeStatement('DELETE FROM external_api_tokens');
        $conn->executeStatement('DELETE FROM auth_tokens');
        $conn->executeStatement('DELETE FROM users');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(string $username): User
    {
        $user = (new User())
            ->setUsername($username)
            ->setEmail($username . '@test.local')
            ->setName('Test')
            ->setLastName('User');

        $this->em->persist($user);
        return $user;
    }

    private function makePhoto(User $user, array $fields = []): Photo
    {
        $photo = (new Photo())
            ->setImageUrl('https://example.com/' . uniqid() . '.jpg')
            ->setUser($user)
            ->setLocation($fields['location'] ?? null)
            ->setDescription($fields['description'] ?? null)
            ->setCamera($fields['camera'] ?? null)
            ->setTakenAt($fields['takenAt'] ?? null);

        $this->em->persist($photo);
        return $photo;
    }

    private function flush(): void
    {
        $this->em->flush();
        $this->em->clear();
    }

    private function filter(array $fields): PhotoFilterDTO
    {
        return new PhotoFilterDTO(
            location: $fields['location'] ?? null,
            camera: $fields['camera'] ?? null,
            description: $fields['description'] ?? null,
            takenAtFrom: $fields['takenAtFrom'] ?? null,
            takenAtTo: $fields['takenAtTo'] ?? null,
            username: $fields['username'] ?? null,
        );
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testFilterByLocationReturnsMatchingPhotos(): void
    {
        $user = $this->makeUser('alice');
        $this->makePhoto($user, ['location' => 'Rocky Mountains, Colorado']);
        $this->makePhoto($user, ['location' => 'Sahara Desert']);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter(['location' => 'Rocky']));

        $this->assertCount(1, $results);
        $this->assertSame('Rocky Mountains, Colorado', $results[0]->getLocation());
    }

    public function testFilterByCameraReturnsMatchingPhotos(): void
    {
        $user = $this->makeUser('bob');
        $this->makePhoto($user, ['camera' => 'Canon EOS R5']);
        $this->makePhoto($user, ['camera' => 'Nikon Z6']);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter(['camera' => 'Canon']));

        $this->assertCount(1, $results);
        $this->assertSame('Canon EOS R5', $results[0]->getCamera());
    }

    public function testFilterByDescriptionReturnsMatchingPhotos(): void
    {
        $user = $this->makeUser('carol');
        $this->makePhoto($user, ['description' => 'Beautiful sunrise over the mountains']);
        $this->makePhoto($user, ['description' => 'City skyline at night']);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter(['description' => 'sunrise']));

        $this->assertCount(1, $results);
        $this->assertStringContainsString('sunrise', $results[0]->getDescription());
    }

    public function testFilterByTakenAtFromExcludesOlderPhotos(): void
    {
        $user = $this->makeUser('dave');
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-01-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-06-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-12-15')]);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter([
            'takenAtFrom' => new \DateTimeImmutable('2024-06-01'),
        ]));

        $this->assertCount(2, $results);
    }

    public function testFilterByTakenAtToExcludesNewerPhotos(): void
    {
        $user = $this->makeUser('eve');
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-01-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-06-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-12-15')]);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter([
            'takenAtTo' => new \DateTimeImmutable('2024-06-15'),
        ]));

        $this->assertCount(2, $results);
    }

    public function testFilterByTakenAtRangeReturnsSinglePhoto(): void
    {
        $user = $this->makeUser('frank');
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-01-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-06-15')]);
        $this->makePhoto($user, ['takenAt' => new \DateTimeImmutable('2024-12-15')]);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter([
            'takenAtFrom' => new \DateTimeImmutable('2024-06-01'),
            'takenAtTo'   => new \DateTimeImmutable('2024-07-01'),
        ]));

        $this->assertCount(1, $results);
    }

    public function testFilterByUsernameReturnsOnlyThatUsersPhotos(): void
    {
        $alice = $this->makeUser('alice_filter');
        $bob   = $this->makeUser('bob_filter');
        $this->makePhoto($alice);
        $this->makePhoto($alice);
        $this->makePhoto($bob);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter(['username' => 'alice_filter']));

        $this->assertCount(2, $results);
        foreach ($results as $photo) {
            $this->assertSame('alice_filter', $photo->getUser()->getUsername());
        }
    }

    public function testCombinedFiltersNarrowResults(): void
    {
        $alice = $this->makeUser('alice_combo');
        $bob   = $this->makeUser('bob_combo');

        $this->makePhoto($alice, ['location' => 'Rocky Mountains', 'camera' => 'Canon EOS R5']);
        $this->makePhoto($alice, ['location' => 'Rocky Mountains', 'camera' => 'Nikon Z6']);
        $this->makePhoto($bob,   ['location' => 'Rocky Mountains', 'camera' => 'Canon EOS R5']);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter([
            'location' => 'Rocky',
            'camera'   => 'Canon',
            'username' => 'alice_combo',
        ]));

        $this->assertCount(1, $results);
        $this->assertSame('alice_combo', $results[0]->getUser()->getUsername());
        $this->assertSame('Canon EOS R5', $results[0]->getCamera());
    }

    public function testNoMatchingFiltersReturnsEmptyArray(): void
    {
        $user = $this->makeUser('ghost');
        $this->makePhoto($user, ['location' => 'Antarctica']);
        $this->flush();

        $results = $this->repo->findWithFilters($this->filter(['location' => 'Mars']));

        $this->assertCount(0, $results);
    }
}

