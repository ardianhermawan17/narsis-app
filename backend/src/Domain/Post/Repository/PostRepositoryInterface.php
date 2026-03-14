<?php

declare(strict_types=1);

namespace App\Domain\Post\Repository;

use App\Domain\Post\Post;

interface PostRepositoryInterface
{
    public function save(Post $post): void;

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdWithImages(string $postId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestWithImages(int $limit = 20): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserIdWithImages(string $userId, int $limit = 20): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLikedByUserIdWithImages(string $userId, int $limit = 20): array;
}