<?php

declare(strict_types=1);

namespace App\Application\Command\AddComment;

use App\Application\Exception\ValidationException;
use App\Domain\Comment\Comment;
use App\Domain\Comment\Repository\CommentRepositoryInterface;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Infrastructure\ID\SnowflakeGenerator;

final class AddCommentHandler
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly PostRepositoryInterface $posts,
        private readonly SnowflakeGenerator $idGenerator,
        private readonly \PDO $pdo
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(AddCommentCommand $command): array
    {
        $postId = trim($command->postId);
        if ($postId === '') {
            throw new ValidationException('postId is required.');
        }

        $content = trim($command->content);
        if ($content === '') {
            throw new ValidationException('content is required.');
        }

        if ($this->posts->findByIdWithImages($postId) === null) {
            throw new ValidationException('Post not found.');
        }

        $parentCommentId = $command->parentCommentId !== null ? trim($command->parentCommentId) : null;
        if ($parentCommentId === '') {
            $parentCommentId = null;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $comment = new Comment(
            $this->idGenerator->nextId(),
            $postId,
            $command->userId,
            $parentCommentId,
            $content,
            $now,
            $now
        );

        $this->pdo->beginTransaction();

        try {
            $this->comments->save($comment);
            $this->comments->incrementPostComments($postId, $now);
            $this->pdo->commit();

            return $comment->toArray();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}