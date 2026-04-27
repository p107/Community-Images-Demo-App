<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExternalApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalApiTokenRepository::class)]
#[ORM\Table(name: 'external_api_tokens')]
#[ORM\UniqueConstraint(name: 'unique_user_service', columns: ['user_id', 'service_name'])]
class ExternalApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 64)]
    private string $serviceName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $token;

    public function __construct(User $user, string $serviceName, string $token)
    {
        $this->user = $user;
        $this->serviceName = $serviceName;
        $this->token = $token;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
}

