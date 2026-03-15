<?php

declare(strict_types=1);

namespace App\Application\Command\Logout;

use App\Application\Exception\UnauthorizedException;
use App\Domain\Session\Repository\SessionRepositoryInterface;
use App\Infrastructure\Auth\JwtProvider;

final class LogoutHandler
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly JwtProvider $jwtProvider
    ) {
    }

    public function handle(LogoutCommand $command): bool
    {
        $claims = $this->jwtProvider->verifyRefreshToken($command->refreshToken);

        $sessionId = (string) ($claims['sid'] ?? '');
        if ($sessionId === '') {
            throw new UnauthorizedException('Refresh token is invalid or expired.');
        }

        $refreshHash = hash('sha256', $command->refreshToken);
        $revoked = $this->sessions->revokeSession($sessionId, $refreshHash);

        if (!$revoked) {
            throw new UnauthorizedException('Refresh token is invalid or expired.');
        }

        return true;
    }
}