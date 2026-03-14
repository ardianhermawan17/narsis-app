<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver\post;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;

final class UserPostResolver
{
    public function __construct(
        private readonly PostController $postController,
        private readonly JwtAuthMiddleware $authMiddleware,
        private readonly GraphQlErrorHandler $errorHandler
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($rootValue, array $args, array $context): array
    {
        return $this->errorHandler->handle(function () use ($args, $context): array {
            $authorizationHeader = $context['authorizationHeader'] ?? null;
            $user = $this->authMiddleware->authenticate(is_string($authorizationHeader) ? $authorizationHeader : null);
            $limit = isset($args['limit']) ? (int) $args['limit'] : 20;

            $response = $this->postController->listByUser($user, $limit);

            return (array) $response['body']['posts'];
        });
    }
}