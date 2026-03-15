<?php

declare(strict_types=1);

namespace App\Domain\Comment\Repository;

use App\Domain\Comment\Comment;

interface CommentRepositoryInterface
{
    public function save(Comment $comment): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserId(string $userId, int $limit = 20): array;

    public function incrementPostComments(string $postId, \DateTimeImmutable $updatedAt): void;
}