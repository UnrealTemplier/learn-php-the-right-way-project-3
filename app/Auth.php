<?php

declare(strict_types=1);

namespace App;

use App\Contracts\UserInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManager;

class Auth implements Contracts\AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(private readonly EntityManager $entityManager) {}

    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $_SESSION['user'] ?? null;

        if (!$userId) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }
}