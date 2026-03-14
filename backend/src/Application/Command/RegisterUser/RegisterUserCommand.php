<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password
    ) {
    }
}
