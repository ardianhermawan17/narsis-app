<?php

declare(strict_types=1);

namespace App\ReadModel\Repository;

use App\ReadModel\UserFeedItem;

interface UserFeedRepositoryInterface
{
    public function addPostForAuthorAndFollowers(string $authorUserId, string $postId, string $score, \DateTimeImmutable $insertedAt): void;

    public function upsertPostForUser(string $userId, string $postId, string $score, \DateTimeImmutable $insertedAt): void;

    /**
     * @return array<int, UserFeedItem>
     */
    public function findByUserId(string $userId, int $limit = 20): array;
}