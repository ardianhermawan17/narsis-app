<?php

declare(strict_types=1);

namespace App\Application\Command\RefreshToken;

final class RefreshTokenCommand
{
    public function __construct(public readonly string $refreshToken)
    {
    }
}