<?php

declare(strict_types=1);

namespace App\Application\Query\ListComments;

use App\Application\Exception\ValidationException;
use App\Domain\Comment\Repository\CommentRepositoryInterface;

final class ListCommentsQueryHandler
{
    public function __construct(private readonly CommentRepositoryInterface $comments)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $userId, int $limit = 20): array
    {
        $userId = trim($userId);
        if ($userId === '') {
            throw new ValidationException('userId is required.');
        }

        if ($limit < 1) {
            $limit = 1;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        return $this->comments->findByUserId($userId, $limit);
    }
}