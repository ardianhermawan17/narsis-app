<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver\post;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\PostController;

final class PostsResolver
{
    public function __construct(
        private readonly PostController $postController,
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
            $response = $this->postController->list($limit);

            return (array) $response['body']['posts'];
        });
    }
}