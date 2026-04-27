<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Repository\LikeRepository;
use App\Service\LikeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PhotoController extends AbstractController
{
    public function __construct(
        private readonly LikeRepository $likeRepository,
        private readonly LikeService $likeService,
    ) {}

    #[Route('/photo/{id}/like', name: 'photo_like')]
    public function like($id, Request $request, EntityManagerInterface $em): Response
    {

        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to like photos.');
            return $this->redirectToRoute('home');
        }

        $user = $em->getRepository(User::class)->find($userId);
        $photo = $em->getRepository(Photo::class)->find($id);

        $this->likeRepository->setUser($user);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

        if ($this->likeRepository->hasUserLikedPhoto($photo)) {
            $this->likeRepository->unlikePhoto($photo);
            $this->addFlash('info', 'Photo unliked!');
        } else {
            $this->likeService->execute($photo);
            $this->addFlash('success', 'Photo liked!');
        }

        return $this->redirectToRoute('home');
    }
}
