<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PhotoFilterDTO;
use App\Repository\LikeRepository;
use App\Service\CurrentUserProvider;
use App\Shared\Photo\PhotoRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly PhotoRepositoryInterface $photoRepository,
        private readonly LikeRepository $likeRepository,
        private readonly CurrentUserProvider $currentUserProvider,
    ) {}

    /**
     * @Route("/", name="home")
     */
    public function index(Request $request): Response
    {
        $filter = PhotoFilterDTO::fromRequest($request);

        $photos = $filter->isEmpty()
            ? $this->photoRepository->findAllWithUsers()
            : $this->photoRepository->findWithFilters($filter);

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
            'filter' => $filter,
        ]);
    }
}
