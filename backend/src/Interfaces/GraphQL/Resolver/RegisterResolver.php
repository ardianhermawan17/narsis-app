<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\AuthController;

final class RegisterResolver
{
    public function __construct(
        private readonly AuthController $authController,
        private readonly GraphQlErrorHandler $errorHandler
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($rootValue, array $args): array
    {
        return $this->errorHandler->handle(function () use ($args): array {
            $response = $this->authController->register($args);
            return (array) $response['body']['user'];
        });
    }
}
