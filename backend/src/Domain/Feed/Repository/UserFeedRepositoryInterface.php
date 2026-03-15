<?php

declare(strict_types=1);

namespace App\Domain\Feed\Repository;

use App\Domain\Feed\UserFeed;

interface UserFeedRepositoryInterface
{
    public function addPostForAuthorAndFollowers(string $authorUserId, string $postId, string $score, \DateTimeImmutable $insertedAt): void;

    public function upsertPostForUser(string $userId, string $postId, string $score, \DateTimeImmutable $insertedAt): void;

    /**
     * @return array<int, UserFeed>
     */
    public function findByUserId(string $userId, int $limit = 20): array;
}