<?php

declare(strict_types=1);

namespace App\Application\Command\RefreshToken;

use App\Application\Exception\UnauthorizedException;
use App\Domain\Session\Repository\SessionRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtProvider;

final class RefreshTokenHandler
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly UserRepositoryInterface $users,
        private readonly JwtProvider $jwtProvider
    ) {
    }

    /**
     * @return array{accessToken:string,refreshToken:string,tokenType:string,expiresIn:int}
     */
    public function handle(RefreshTokenCommand $command): array
    {
        $claims = $this->jwtProvider->verifyRefreshToken($command->refreshToken);

        $sessionId = $claims['sid'];
        $userId = $claims['sub'];
        $currentRefreshHash = hash('sha256', $command->refreshToken);

        if (!$this->sessions->hasActiveSession($sessionId, $currentRefreshHash)) {
            throw new UnauthorizedException('Refresh token is invalid or expired.');
        }

        if ($this->users->findById($userId) === null) {
            throw new UnauthorizedException('Authenticated user not found.');
        }

        $newRefreshToken = $this->jwtProvider->createRefreshToken($userId, $sessionId);
        $newRefreshHash = hash('sha256', $newRefreshToken);
        $refreshExpiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . $this->jwtProvider->refreshTtlSeconds() . ' seconds');

        $rotated = $this->sessions->rotateRefreshToken(
            $sessionId,
            $currentRefreshHash,
            $newRefreshHash,
            $refreshExpiresAt
        );

        if (!$rotated) {
            throw new UnauthorizedException('Refresh token is invalid or expired.');
        }

        return [
            'accessToken' => $this->jwtProvider->createAccessToken($userId),
            'refreshToken' => $newRefreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => $this->jwtProvider->accessTtlSeconds(),
        ];
    }
}