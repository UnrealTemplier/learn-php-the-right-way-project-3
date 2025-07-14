<?php

declare(strict_types=1);

namespace App\Contracts;

interface SessionInterface
{

    public function start(): void;

    public function save(): void;

    public function isActive(): bool;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;

    public function regenerate(): bool;

    public function flash(string $key, array $data): void;

    public function getFlash(string $key): array;
}