<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;

interface UserProviderServiceInterface
{
    public function getById(int $userId): ?UserInterface;

    public function getByCredentials(LoginData $credentials): ?UserInterface;

    public function create(RegisterUserData $data): ?UserInterface;
}