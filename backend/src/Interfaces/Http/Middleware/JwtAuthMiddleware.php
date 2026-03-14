<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Middleware;

use App\Application\Exception\UnauthorizedException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Auth\JwtProvider;

final class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtProvider $jwtProvider,
        private readonly UserRepositoryInterface $users
    ) {
    }

    public function authenticate(?string $authorizationHeader): User
    {
        if ($authorizationHeader === null || !str_starts_with($authorizationHeader, 'Bearer ')) {
            throw new UnauthorizedException('Missing or invalid Authorization header.');
        }

        $token = trim(substr($authorizationHeader, 7));
        $claims = $this->jwtProvider->verifyAccessToken($token);
        $user = $this->users->findById($claims['sub']);

        if ($user === null) {
            throw new UnauthorizedException('Authenticated user not found.');
        }

        return $user;
    }
}
