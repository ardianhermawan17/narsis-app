<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Session\Repository\SessionRepositoryInterface;

final class PgSessionRepository implements SessionRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function createSession(
        string $sessionId,
        string $userId,
        string $refreshTokenHash,
        ?array $clientInfo,
        \DateTimeImmutable $expiresAt
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, user_id, refresh_token_hash, client_info, expires_at, created_at) '
            . 'VALUES (:id, :user_id, :refresh_token_hash, :client_info, :expires_at, :created_at)'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare session insert statement.');
        }

        $clientInfoJson = $clientInfo !== null
            ? (string) json_encode($clientInfo, JSON_THROW_ON_ERROR)
            : null;

        try {
            $stmt->execute([
                ':id' => $sessionId,
                ':user_id' => $userId,
                ':refresh_token_hash' => $refreshTokenHash,
                ':client_info' => $clientInfoJson,
                ':expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
                ':created_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:sP'),
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Failed to create session.', 0, $e);
        }
    }

    public function hasActiveSession(string $sessionId, string $refreshTokenHash): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM sessions '
            . 'WHERE id = :id AND refresh_token_hash = :refresh_token_hash '
            . 'AND (expires_at IS NULL OR expires_at > NOW()) '
            . 'LIMIT 1'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare active session query.');
        }

        $stmt->execute([
            ':id' => $sessionId,
            ':refresh_token_hash' => $refreshTokenHash,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function rotateRefreshToken(
        string $sessionId,
        string $currentRefreshTokenHash,
        string $newRefreshTokenHash,
        \DateTimeImmutable $newExpiresAt
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE sessions '
            . 'SET refresh_token_hash = :new_refresh_token_hash, expires_at = :expires_at '
            . 'WHERE id = :id AND refresh_token_hash = :current_refresh_token_hash '
            . 'AND (expires_at IS NULL OR expires_at > NOW())'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare session rotation statement.');
        }

        $stmt->execute([
            ':new_refresh_token_hash' => $newRefreshTokenHash,
            ':expires_at' => $newExpiresAt->format('Y-m-d H:i:sP'),
            ':id' => $sessionId,
            ':current_refresh_token_hash' => $currentRefreshTokenHash,
        ]);

        return $stmt->rowCount() > 0;
    }
}