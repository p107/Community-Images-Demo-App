<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Repository\LikeRepository;
use App\Service\CurrentUserProvider;
use App\Service\LikeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhotoController extends AbstractController
{
    public function __construct(
        private readonly LikeRepository $likeRepository,
        private readonly LikeService $likeService,
        private readonly CurrentUserProvider $currentUserProvider,
    ) {}

    #[Route('/photo/{id}/like', name: 'photo_like')]
    public function like(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user) {
            $this->addFlash('error', 'You must be logged in to like photos.');
            return $this->redirectToRoute('home');
        }

        $photo = $em->getRepository(Photo::class)->find($id);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

        if ($this->likeRepository->hasUserLikedPhoto($photo, $user)) {
            $this->likeRepository->unlikePhoto($photo, $user);
            $this->addFlash('info', 'Photo unliked!');
        } else {
            $this->likeService->execute($photo, $user);
            $this->addFlash('success', 'Photo liked!');
        }

        return $this->redirectToRoute('home');
    }
}
