<?php

declare(strict_types=1);

namespace App\Application\Command\LikePost;

use App\Application\Exception\AlreadyLikedException;
use App\Application\Exception\ValidationException;
use App\Domain\Like\Repository\LikeRepositoryInterface;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Infrastructure\ID\SnowflakeGenerator;
use App\ReadModel\Repository\UserFeedRepositoryInterface;

final class LikePostHandler
{
    public function __construct(
        private readonly LikeRepositoryInterface $likes,
        private readonly PostRepositoryInterface $posts,
        private readonly UserFeedRepositoryInterface $feed,
        private readonly SnowflakeGenerator $idGenerator,
        private readonly \PDO $pdo
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(LikePostCommand $command): array
    {
        $postId = trim($command->postId);
        if ($postId === '') {
            throw new ValidationException('postId is required.');
        }

        $existingPost = $this->posts->findByIdWithImages($postId);
        if ($existingPost === null) {
            throw new ValidationException('Post not found.');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->pdo->beginTransaction();

        try {
            $created = $this->likes->createLike(
                $this->idGenerator->nextId(),
                $postId,
                $command->userId,
                $now
            );

            if (!$created) {
                throw new AlreadyLikedException();
            }

            $this->likes->incrementPostLikes($postId, $now);
            $this->feed->upsertPostForUser($command->userId, $postId, (string) $now->format('U.u'), $now);

            $this->pdo->commit();

            $updatedPost = $this->posts->findByIdWithImages($postId);
            if ($updatedPost === null) {
                throw new \RuntimeException('Post disappeared after like operation.');
            }

            return $updatedPost;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
