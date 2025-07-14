<?php

declare(strict_types=1);

namespace App;

use App\Contracts\UserInterface;
use App\Entity\User;
use App\Exception\ValidationException;
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

    public function attemptLogin(array $credentials): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user || !password_verify($credentials['password'], $user->getPassword())) {
            return false;
        }

        session_regenerate_id();

        $this->user = $user;
        $_SESSION['user'] = $this->user->getId();

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        $this->user = null;
    }
}