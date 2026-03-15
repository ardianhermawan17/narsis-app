<?php

declare(strict_types=1);

namespace App\Application\Command\Logout;

final class LogoutCommand
{
    public function __construct(public readonly string $refreshToken)
    {
    }
}