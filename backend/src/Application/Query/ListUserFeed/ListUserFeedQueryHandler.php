<?php

declare(strict_types=1);

namespace App\Application\Query\ListUserFeed;

use App\ReadModel\UserFeedItem;
use App\ReadModel\Repository\UserFeedRepositoryInterface;

final class ListUserFeedQueryHandler
{
    public function __construct(private readonly UserFeedRepositoryInterface $feed)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $items = $this->feed->findByUserId($userId, $limit);

        return array_map(
            static fn (UserFeedItem $item): array => $item->toArray(),
            $items
        );
    }
}
