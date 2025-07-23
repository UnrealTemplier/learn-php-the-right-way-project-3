<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;
use App\DataObjects\TwoFactorLoginData;
use App\Enum\AuthAttemptStatus;

interface AuthInterface
{
    public function user(): ?UserInterface;

    public function attemptLogin(LoginData $credentials): AuthAttemptStatus;

    public function logout(): void;

    public function register(RegisterUserData $data): UserInterface;

    public function attemptTwoFactorLogin(TwoFactorLoginData $data): bool;
}