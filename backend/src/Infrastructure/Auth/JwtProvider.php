<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtProvider
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds
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

        return [
            'sub' => (string) $decoded->sub,
            'iat' => (int) $decoded->iat,
            'exp' => (int) $decoded->exp,
        ];
    }
}
