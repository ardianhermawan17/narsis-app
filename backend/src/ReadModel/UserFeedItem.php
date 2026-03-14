<?php

declare(strict_types=1);

namespace App\ReadModel;

final class UserFeedItem
{
    /**
     * @param array<int, array<string, mixed>> $images
     */
    public function __construct(
        private readonly string $postId,
        private readonly string $authorUserId,
        private readonly ?string $caption,
        private readonly string $visibility,
        private readonly int $likesCount,
        private readonly string $score,
        private readonly string $insertedAt,
        private readonly string $createdAt,
        private readonly string $updatedAt,
        private readonly array $images
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->postId,
            'userId' => $this->authorUserId,
            'caption' => $this->caption,
            'visibility' => $this->visibility,
            'likesCount' => $this->likesCount,
            'score' => $this->score,
            'insertedAt' => $this->insertedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'images' => $this->images,
        ];
    }
}
