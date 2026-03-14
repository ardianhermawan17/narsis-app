<?php

declare(strict_types=1);

namespace App\Application\Query\ListPosts;

use App\Domain\Post\Repository\PostRepositoryInterface;

final class ListPostsQueryHandler
{
    public function __construct(private readonly PostRepositoryInterface $posts)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $limit = 20): array
    {
        if ($limit < 1) {
            $limit = 1;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        return $this->posts->findLatestWithImages($limit);
    }
}