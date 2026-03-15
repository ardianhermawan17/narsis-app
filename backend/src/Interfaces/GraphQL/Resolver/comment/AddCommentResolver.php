<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver\comment;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\CommentController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;

final class AddCommentResolver
{
    public function __construct(
        private readonly CommentController $commentController,
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

            $response = $this->commentController->add($user, [
                'postId' => $args['postId'] ?? '',
                'content' => $args['content'] ?? '',
                'parentCommentId' => $args['parentCommentId'] ?? null,
            ]);

            return (array) $response['body']['comment'];
        });
    }
}