<?php

declare(strict_types=1);

namespace App\Application\Command\UnlikePost;

use App\Application\Exception\NotLikedException;
use App\Application\Exception\ValidationException;
use App\Domain\Like\Repository\LikeRepositoryInterface;
use App\Domain\Post\Repository\PostRepositoryInterface;

final class UnlikePostHandler
{
    public function __construct(
        private readonly LikeRepositoryInterface $likes,
        private readonly PostRepositoryInterface $posts,
        private readonly \PDO $pdo
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(UnlikePostCommand $command): array
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
            $deleted = $this->likes->deleteLike($postId, $command->userId);

            if (!$deleted) {
                throw new NotLikedException();
            }

            $this->likes->decrementPostLikes($postId, $now);

            $this->pdo->commit();

            $updatedPost = $this->posts->findByIdWithImages($postId);
            if ($updatedPost === null) {
                throw new \RuntimeException('Post disappeared after unlike operation.');
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