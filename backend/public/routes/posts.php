<?php

declare(strict_types=1);

return static function (string $method, string $path, array $payload, array $services, callable $resolveAuthorizationHeader): ?array {
    $postController = $services['postController'];
    $authMiddleware = $services['authMiddleware'];
    $httpErrorHandler = $services['httpErrorHandler'];

    if ($method === 'POST' && $path === '/api/posts') {
        try {
            $user = $authMiddleware->authenticate($resolveAuthorizationHeader());
            $response = $postController->create($user, $payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    if ($method === 'GET' && $path === '/api/posts') {
        try {
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $response = $postController->list($limit);
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