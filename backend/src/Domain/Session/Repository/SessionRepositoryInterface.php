<?php

declare(strict_types=1);

namespace App\Domain\Session\Repository;

interface SessionRepositoryInterface
{
    public function createSession(
        string $sessionId,
        string $userId,
        string $refreshTokenHash,
        ?array $clientInfo,
        \DateTimeImmutable $expiresAt
    ): void;

    public function hasActiveSession(string $sessionId, string $refreshTokenHash): bool;

    public function rotateRefreshToken(
        string $sessionId,
        string $currentRefreshTokenHash,
        string $newRefreshTokenHash,
        \DateTimeImmutable $newExpiresAt
    ): bool;

    public function revokeSession(string $sessionId, string $refreshTokenHash): bool;
}