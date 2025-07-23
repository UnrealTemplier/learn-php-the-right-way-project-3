<?php

declare(strict_types=1);

namespace App\Contracts;

interface UserInterface
{
    public function getId(): int;

    public function getName(): string;

    public function getEmail(): string;

    public function getPassword(): string;

    public function setVerifiedAt(\DateTime $verifiedAt): static;

    public function hasTwoFactorAuthEnabled(): bool;
}