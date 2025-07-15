<?php

declare(strict_types=1);

namespace App\DataObjects;

class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
