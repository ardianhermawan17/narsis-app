<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Resolver;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\Http\Controller\AuthController;

final class LoginResolver
{
    public function __construct(
        private readonly AuthController $authController,
        private readonly GraphQlErrorHandler $errorHandler
    ) {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke($rootValue, array $args): string
    {
        return $this->errorHandler->handle(function () use ($args): string {
            $response = $this->authController->login($args);
            return (string) $response['body']['accessToken'];
        });
    }
}
