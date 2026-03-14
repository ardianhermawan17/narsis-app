<?php

declare(strict_types=1);

namespace App\Domain\Post\Repository;

use App\Domain\Post\Post;

interface PostRepositoryInterface
{
    public function save(Post $post): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestWithImages(int $limit = 20): array;
}