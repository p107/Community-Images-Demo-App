<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CurrentUserProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly CurrentUserProvider $currentUserProvider,
    ) {}

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user) {
            return $this->redirectToRoute('home');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
