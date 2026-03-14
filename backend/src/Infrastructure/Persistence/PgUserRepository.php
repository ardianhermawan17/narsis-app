<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final class PgUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(User $user): void
    {
        $sql = 'INSERT INTO users (id, username, email, password_hash, display_name, bio, created_at, updated_at)'
             . ' VALUES (:id, :username, :email, :password_hash, :display_name, :bio, :created_at, :updated_at)';
        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user insert statement.');
        }

        try {
            $stmt->execute([
                ':id'           => $user->id(),
                ':username'     => $user->username(),
                ':email'        => $user->email(),
                ':password_hash'=> $user->passwordHash(),
                ':display_name' => $user->displayName(),
                ':bio'          => $user->bio(),
                ':created_at'   => $user->createdAt()->format('Y-m-d H:i:sP'),
                ':updated_at'   => $user->updatedAt()->format('Y-m-d H:i:sP'),
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Failed to create user.', 0, $e);
        }
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, display_name, bio, created_at, updated_at'
            . ' FROM users WHERE id = :id LIMIT 1'
        );
        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare profile query.');
        }

        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateUser($row);
    }

    public function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, display_name, bio, created_at, updated_at'
            . ' FROM users WHERE username = :identity OR email = :identity LIMIT 1'
        );
        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare login query.');
        }

        $stmt->execute([':identity' => trim($usernameOrEmail)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateUser($row);
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = :username OR email = :email LIMIT 1');
        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare uniqueness query.');
        }

        $stmt->execute([
            ':username' => trim($username),
            ':email' => strtolower(trim($email)),
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array{id:string,username:string,email:string,password_hash:string,display_name:string|null,bio:string|null,created_at:string,updated_at:string} $row
     */
    private function hydrateUser(array $row): User
    {
        return new User(
            (string) $row['id'],
            (string) $row['username'],
            (string) $row['email'],
            (string) $row['password_hash'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
            isset($row['display_name']) ? (string) $row['display_name'] : null,
            isset($row['bio']) ? (string) $row['bio'] : null
        );
    }
}
