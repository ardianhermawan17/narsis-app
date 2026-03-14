<?php

declare(strict_types=1);

namespace App\Application\Command\LoginUser;

use App\Application\Exception\InvalidCredentialsException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtProvider;

final class LoginUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly JwtProvider $jwtProvider
    ) {
    }

    /**
     * @return array{accessToken:string}
     */
    public function handle(LoginUserCommand $command): array
    {
        $user = $this->users->findByUsernameOrEmail($command->usernameOrEmail);

        if ($user === null || !$user->verifyPassword($command->password)) {
            throw new InvalidCredentialsException();
        }

        return [
            'accessToken' => $this->jwtProvider->createAccessToken($user->id()),
        ];
    }
}
