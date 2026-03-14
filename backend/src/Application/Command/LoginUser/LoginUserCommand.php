<?php

declare(strict_types=1);

namespace App\Application\Command\LoginUser;

final class LoginUserCommand
{
    public function __construct(
        public readonly string $usernameOrEmail,
        public readonly string $password
    ) {
    }
}
