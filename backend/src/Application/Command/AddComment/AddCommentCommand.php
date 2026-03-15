<?php

declare(strict_types=1);

namespace App\Application\Command\AddComment;

final class AddCommentCommand
{
    public function __construct(
        public readonly string $postId,
        public readonly string $userId,
        public readonly string $content,
        public readonly ?string $parentCommentId = null
    ) {
    }
}