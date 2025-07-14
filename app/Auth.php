<?php

declare(strict_types=1);

namespace App;

use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;

class Auth implements Contracts\AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(private readonly UserProviderServiceInterface $userProviderService) {}

    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $_SESSION['user'] ?? null;

        if (!$userId) {
            return null;
        }

        $user = $this->userProviderService->getById($userId);

        if (!$user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function attemptLogin(array $credentials): bool
    {
        $user = $this->userProviderService->getByCredentials($credentials);

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