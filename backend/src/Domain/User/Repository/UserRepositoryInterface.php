<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(string $id): ?User;

    public function findByUsernameOrEmail(string $usernameOrEmail): ?User;

    public function existsByUsernameOrEmail(string $username, string $email): bool;
}
