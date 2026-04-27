<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentUserProvider
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    public function getUser(): ?User
    {
        $session = $this->requestStack->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return null;
        }
        return $this->em->getRepository(User::class)->find($userId);
    }
}
