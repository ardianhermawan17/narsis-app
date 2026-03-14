<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterUser;

use App\Application\Exception\IdentityAlreadyExistsException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\ID\SnowflakeGenerator;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly SnowflakeGenerator $idGenerator
    ) {
    }

    public function handle(RegisterUserCommand $command): User
    {
        if ($this->users->existsByUsernameOrEmail($command->username, $command->email)) {
            throw new IdentityAlreadyExistsException();
        }

        $algo = \defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $passwordHash = password_hash($command->password, $algo);
        if ($passwordHash === false) {
            throw new \RuntimeException('Failed to hash password.');
        }

        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $user = new User(
            $this->idGenerator->nextId(),
            trim($command->username),
            strtolower(trim($command->email)),
            $passwordHash,
            $now,
            $now
        );

        $this->users->save($user);

        return $user;
    }
}
