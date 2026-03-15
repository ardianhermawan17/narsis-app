<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver\comment;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\CommentController;

final class CommentsResolver
{
    public function __construct(
        private readonly CommentController $commentController,
        private readonly GraphQlErrorHandler $errorHandler
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($rootValue, array $args): array
    {
        return $this->errorHandler->handle(function () use ($args): array {
            $limit = isset($args['limit']) ? (int) $args['limit'] : 20;
            $response = $this->commentController->list((string) ($args['postId'] ?? ''), $limit);

            return (array) $response['body']['comments'];
        });
    }
}