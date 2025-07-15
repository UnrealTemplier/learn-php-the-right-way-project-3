<?php

declare(strict_types=1);

namespace App\Contracts;

interface AuthInterface
{
    public function user(): ?UserInterface;

    public function attemptLogin(array $credentials): bool;

    public function logout(): void;

    public function register(array $data): UserInterface;
}