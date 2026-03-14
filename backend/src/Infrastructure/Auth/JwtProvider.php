<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtProvider
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
        private readonly int $refreshTtlSeconds = 1209600
    ) {
        if ($this->secret === '') {
            throw new \InvalidArgumentException('JWT secret cannot be empty.');
        }
    }

    public function createAccessToken(string $userId): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttlSeconds;

        $payload = [
            'sub' => $userId,
            'typ' => 'access',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function createRefreshToken(string $userId, string $sessionId): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->refreshTtlSeconds;

        $payload = [
            'sub' => $userId,
            'sid' => $sessionId,
            'typ' => 'refresh',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * @return array{sub:string,iat:int,exp:int}
     */
    public function verifyAccessToken(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

        if (!isset($decoded->typ) || $decoded->typ !== 'access') {
            throw new \RuntimeException('Invalid access token type.');
        }

        return [
            'sub' => (string) $decoded->sub,
            'iat' => (int) $decoded->iat,
            'exp' => (int) $decoded->exp,
        ];
    }

    /**
     * @return array{sub:string,sid:string,iat:int,exp:int}
     */
    public function verifyRefreshToken(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

        if (!isset($decoded->typ) || $decoded->typ !== 'refresh' || !isset($decoded->sid)) {
            throw new \RuntimeException('Invalid refresh token.');
        }

        return [
            'sub' => (string) $decoded->sub,
            'sid' => (string) $decoded->sid,
            'iat' => (int) $decoded->iat,
            'exp' => (int) $decoded->exp,
        ];
    }

    public function accessTtlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    public function refreshTtlSeconds(): int
    {
        return $this->refreshTtlSeconds;
    }
}
