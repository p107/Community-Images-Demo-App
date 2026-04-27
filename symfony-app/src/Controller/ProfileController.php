<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ExternalApiToken;
use App\Repository\ExternalApiTokenRepository;
use App\Service\CurrentUserProvider;
use App\Service\PhotoImportService;
use App\Shared\PhoenixApi\InvalidTokenException;
use App\Shared\PhoenixApi\RateLimitExceededException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly ExternalApiTokenRepository $externalApiTokenRepository,
        private readonly EntityManagerInterface $em,
        private readonly PhotoImportService $photoImportService,
    ) {}

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user) {
            return $this->redirectToRoute('home');
        }

        $externalToken = $this->externalApiTokenRepository->findByUserAndService($user, 'phoenix_api');

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'phoenixApiToken' => $externalToken?->getToken(),
        ]);
    }

    #[Route('/profile/save-token', name: 'profile_save_token', methods: ['POST'])]
    public function saveToken(Request $request): Response
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user) {
            return $this->redirectToRoute('home');
        }

        if (!$this->isCsrfTokenValid('phoenix_token', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('profile');
        }

        $tokenValue = trim((string) $request->request->get('phoenix_api_token', ''));

        if ($tokenValue === '') {
            $this->addFlash('error', 'Token cannot be empty.');
            return $this->redirectToRoute('profile');
        }

        $externalToken = $this->externalApiTokenRepository->findByUserAndService($user, 'phoenix_api');

        if ($externalToken) {
            $externalToken->setToken($tokenValue);
        } else {
            $externalToken = new ExternalApiToken($user, 'phoenix_api', $tokenValue);
            $this->em->persist($externalToken);
        }

        $this->em->flush();

        $this->addFlash('success', 'PhoenixApi token saved successfully.');

        return $this->redirectToRoute('profile');
    }

    #[Route('/profile/import', name: 'profile_import_photos', methods: ['POST'])]
    public function importPhotos(Request $request): Response
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user) {
            return $this->redirectToRoute('home');
        }

        if (!$this->isCsrfTokenValid('import_photos', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('profile');
        }

        try {
            $imported = $this->photoImportService->importForUser($user);
            $this->addFlash('success', sprintf('Successfully imported %d photo(s).', $imported));
        } catch (RateLimitExceededException) {
            $this->addFlash('error', 'Import limit exceeded. You can import photos at most 5 times per 10 minutes. Please try again later.');
        } catch (InvalidTokenException) {
            $this->addFlash('error', 'Invalid PhoenixApi token. Please update your token and try again.');
        } catch (\RuntimeException) {
            $this->addFlash('error', 'No PhoenixApi token configured. Please save your token first.');
        }

        return $this->redirectToRoute('profile');
    }
}
