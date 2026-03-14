<?php

declare(strict_types=1);

namespace App\Domain\Like\Repository;

interface LikeRepositoryInterface
{
    public function createLike(string $likeId, string $postId, string $userId, \DateTimeImmutable $createdAt): bool;

    public function deleteLike(string $postId, string $userId): bool;

    public function incrementPostLikes(string $postId, \DateTimeImmutable $updatedAt): void;

    public function decrementPostLikes(string $postId, \DateTimeImmutable $updatedAt): void;
}