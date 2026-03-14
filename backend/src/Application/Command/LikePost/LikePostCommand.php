<?php

declare(strict_types=1);

namespace App\Application\Command\LikePost;

final class LikePostCommand
{
    public function __construct(
        public readonly string $postId,
        public readonly string $userId
    ) {
    }
}
