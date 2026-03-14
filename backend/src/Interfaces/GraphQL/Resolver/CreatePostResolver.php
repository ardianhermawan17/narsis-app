<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;

final class CreatePostResolver
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
     * @return array<string, mixed>
     */
    public function __invoke($rootValue, array $args, array $context): array
    {
        return $this->errorHandler->handle(function () use ($args, $context): array {
            $authorizationHeader = $context['authorizationHeader'] ?? null;
            $user = $this->authMiddleware->authenticate(is_string($authorizationHeader) ? $authorizationHeader : null);

            $response = $this->postController->create($user, [
                'caption' => $args['caption'] ?? null,
                'visibility' => $args['visibility'] ?? 'public',
                'images' => $args['images'] ?? [],
            ]);

            return (array) $response['body']['post'];
        });
    }
}