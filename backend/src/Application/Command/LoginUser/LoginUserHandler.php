<?php

declare(strict_types=1);

namespace App\Application\Command\LoginUser;

use App\Application\Exception\InvalidCredentialsException;
use App\Domain\Session\Repository\SessionRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtProvider;
use App\Infrastructure\ID\SnowflakeGenerator;

final class LoginUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly JwtProvider $jwtProvider,
        private readonly SessionRepositoryInterface $sessions,
        private readonly SnowflakeGenerator $idGenerator
    ) {
    }

    /**
     * @return array{accessToken:string,refreshToken:string,tokenType:string,expiresIn:int}
     */
    public function handle(LoginUserCommand $command): array
    {
        $user = $this->users->findByUsernameOrEmail($command->usernameOrEmail);

        if ($user === null || !$user->verifyPassword($command->password)) {
            throw new InvalidCredentialsException();
        }

        $sessionId = $this->idGenerator->nextId();
        $refreshToken = $this->jwtProvider->createRefreshToken($user->id(), $sessionId);
        $refreshHash = hash('sha256', $refreshToken);
        $refreshExpiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('+' . $this->jwtProvider->refreshTtlSeconds() . ' seconds');

        $this->sessions->createSession(
            $sessionId,
            $user->id(),
            $refreshHash,
            null,
            $refreshExpiresAt
        );

        return [
            'accessToken' => $this->jwtProvider->createAccessToken($user->id()),
            'refreshToken' => $refreshToken,
            'tokenType' => 'Bearer',
            'expiresIn' => $this->jwtProvider->accessTtlSeconds(),
        ];
    }
}
