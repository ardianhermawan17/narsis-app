<?php

declare(strict_types=1);

return static function (string $method, string $path, array $payload, array $services, callable $resolveAuthorizationHeader): ?array {
    $authController = $services['authController'];
    $authMiddleware = $services['authMiddleware'];
    $httpErrorHandler = $services['httpErrorHandler'];

    if ($method === 'POST' && $path === '/api/register') {
        try {
            $response = $authController->register($payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    if ($method === 'POST' && $path === '/api/login') {
        try {
            $response = $authController->login($payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    if ($method === 'POST' && $path === '/api/refresh-token') {
        try {
            $response = $authController->refreshToken($payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    if ($method === 'GET' && $path === '/api/profile') {
        try {
            $user = $authMiddleware->authenticate($resolveAuthorizationHeader());
            $response = $authController->profile($user);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    return null;
};
