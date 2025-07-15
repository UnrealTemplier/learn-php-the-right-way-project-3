<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;

interface AuthInterface
{
    public function user(): ?UserInterface;

    public function attemptLogin(LoginData $credentials): bool;

    public function logout(): void;

    public function register(RegisterUserData $data): UserInterface;
}