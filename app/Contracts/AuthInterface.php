<?php

declare(strict_types=1);

namespace App\Contracts;

interface AuthInterface
{
    public function user(): ?UserInterface;
}