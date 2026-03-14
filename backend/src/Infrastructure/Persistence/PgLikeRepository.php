<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Like\Repository\LikeRepositoryInterface;

final class PgLikeRepository implements LikeRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function createLike(string $likeId, string $postId, string $userId, \DateTimeImmutable $createdAt): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO likes (id, post_id, user_id, created_at) '
            . 'VALUES (:id, :post_id, :user_id, :created_at) '
            . 'ON CONFLICT (user_id, post_id) DO NOTHING'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare like insert statement.');
        }

        $stmt->execute([
            ':id' => $likeId,
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':created_at' => $createdAt->format('Y-m-d H:i:sP'),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteLike(string $postId, string $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare like delete statement.');
        }

        $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function incrementPostLikes(string $postId, \DateTimeImmutable $updatedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO post_counters (post_id, likes_count, comments_count, shares_count, updated_at) '
            . 'VALUES (:post_id, 1, 0, 0, :updated_at) '
            . 'ON CONFLICT (post_id) DO UPDATE '
            . 'SET likes_count = post_counters.likes_count + 1, updated_at = EXCLUDED.updated_at'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare post counter upsert statement.');
        }

        $stmt->execute([
            ':post_id' => $postId,
            ':updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
        ]);
    }

    public function decrementPostLikes(string $postId, \DateTimeImmutable $updatedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO post_counters (post_id, likes_count, comments_count, shares_count, updated_at) '
            . 'VALUES (:post_id, 0, 0, 0, :updated_at) '
            . 'ON CONFLICT (post_id) DO UPDATE '
            . 'SET likes_count = GREATEST(post_counters.likes_count - 1, 0), updated_at = EXCLUDED.updated_at'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare post counter downsert statement.');
        }

        $stmt->execute([
            ':post_id' => $postId,
            ':updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
        ]);
    }
}
