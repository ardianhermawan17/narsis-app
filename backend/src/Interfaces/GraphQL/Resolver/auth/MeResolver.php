<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver\auth;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\AuthController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;

final class MeResolver
{
    public function __construct(
        private readonly AuthController $authController,
        private readonly JwtAuthMiddleware $authMiddleware,
        private readonly GraphQlErrorHandler $errorHandler
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function __invoke($rootValue, array $args, array $context): array
    {
        return $this->errorHandler->handle(function () use ($context): array {
            $authorizationHeader = $context['authorizationHeader'] ?? null;
            $user = $this->authMiddleware->authenticate(is_string($authorizationHeader) ? $authorizationHeader : null);

            return $this->authController->profile($user)['body'];
        });
    }
}
