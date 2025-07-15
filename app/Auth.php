<?php

declare(strict_types=1);

namespace App;

use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;

class Auth implements Contracts\AuthInterface
{
    private ?UserInterface $user = null;

    public function __construct(
        private readonly UserProviderServiceInterface $userProvider,
        private readonly SessionInterface             $session,
    ) {}

    public function user(): ?UserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get('user');

        if (!$userId) {
            return null;
        }

        $user = $this->userProvider->getById($userId);

        if (!$user) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function attemptLogin(array $credentials): bool
    {
        $user = $this->userProvider->getByCredentials($credentials);

        if (!$user || !$this->check_credentials($user, $credentials)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function logout(): void
    {
        $this->session->forget('user');
        $this->session->regenerate();

        $this->user = null;
    }

    private function check_credentials(UserInterface $user, array $credentials): bool
    {
        return password_verify($credentials['password'], $user->getPassword());
    }

    public function register(array $data): UserInterface
    {
        $user = $this->userProvider->create($data);
        if (!$user) {
            throw new \RuntimeException('Failed to create a user');
        }

        $this->login($user);

        return $user;
    }

    private function login(UserInterface $user): void
    {
        $this->user = $user;

        $this->session->regenerate();
        $this->session->put('user', $this->user->getId());
    }
}