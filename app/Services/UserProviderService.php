<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DataObjects\LoginData;
use App\DataObjects\RegisterUserData;
use App\Entity\User;

class UserProviderService implements UserProviderServiceInterface
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager,
        private readonly HashService                   $hashService
    ) {}

    public function getById(int $userId): ?UserInterface
    {
        return $this->entityManager->find(User::class, $userId);
    }

    public function getByCredentials(string $email): ?UserInterface
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function create(RegisterUserData $data): ?UserInterface
    {
        $user = new User();
        $user->setName($data->name);
        $user->setEmail($data->email);
        $user->setPassword($this->hashService->hashPassword($data->password));

        $this->entityManager->sync($user);

        return $user;
    }

    public function verifyUser(UserInterface $user): void
    {
        $user->setVerifiedAt(new \DateTime());
        $this->entityManager->sync($user);
    }
}