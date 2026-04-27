<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LikeRepository;
use App\Repository\PhotoRepository;
use App\Service\CurrentUserProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly PhotoRepository $photoRepository,
        private readonly LikeRepository $likeRepository,
        private readonly CurrentUserProvider $currentUserProvider,
    ) {}

    /**
     * @Route("/", name="home")
     */
    public function index(Request $request): Response
    {
        $photos = $this->photoRepository->findAllWithUsers();

        $currentUser = $this->currentUserProvider->getUser();
        $userLikes = [];

        if ($currentUser) {
            foreach ($photos as $photo) {
                $userLikes[$photo->getId()] = $this->likeRepository->hasUserLikedPhoto($photo, $currentUser);
            }
        }

        return $this->render('home/index.html.twig', [
            'photos' => $photos,
            'currentUser' => $currentUser,
            'userLikes' => $userLikes,
        ]);
    }
}
