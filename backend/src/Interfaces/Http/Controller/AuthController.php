<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\Exception\ValidationException;
use App\Application\Command\LoginUser\LoginUserCommand;
use App\Application\Command\LoginUser\LoginUserHandler;
use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Domain\User\User;

final class AuthController
{
    public function __construct(
        private readonly RegisterUserHandler $registerHandler,
        private readonly LoginUserHandler $loginHandler
    ) {
    }

    /**
     * @param array{username?:string,email?:string,password?:string} $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function register(array $payload): array
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            throw new ValidationException('username, email, and password are required.');
        }

        $user = $this->registerHandler->handle(new RegisterUserCommand($username, $email, $password));

        return [
            'status' => 201,
            'body' => [
                'message' => 'User registered.',
                'user' => $user->toPublicArray(),
            ],
        ];
    }

    /**
     * @param array{usernameOrEmail?:string,password?:string} $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function login(array $payload): array
    {
        $usernameOrEmail = trim((string) ($payload['usernameOrEmail'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($usernameOrEmail === '' || $password === '') {
            throw new ValidationException('usernameOrEmail and password are required.');
        }

        $token = $this->loginHandler->handle(new LoginUserCommand($usernameOrEmail, $password));

        return [
            'status' => 200,
            'body' => $token,
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function profile(User $user): array
    {
        return [
            'status' => 200,
            'body' => $user->toPublicArray(),
        ];
    }

}
