<?php

declare(strict_types=1);

return static function (string $method, string $path): ?array {
    if ($method === 'GET' && $path === '/health') {
        return [
            'status' => 200,
            'body' => [
                'status' => 'ok',
                'message' => 'Service is healthy',
            ],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    if ($method === 'GET' && $path === '/graphql/schema') {
        $schemaPath = dirname(__DIR__, 2) . '/graphql.schema';
        if (!file_exists($schemaPath)) {
            return [
                'status' => 404,
                'body' => ['error' => 'Schema file not found.'],
                'headers' => ['Content-Type' => 'application/json'],
            ];
        }

        return [
            'status' => 200,
            'body' => (string) file_get_contents($schemaPath),
            'headers' => ['Content-Type' => 'text/plain; charset=utf-8'],
        ];
    }

    return null;
};
