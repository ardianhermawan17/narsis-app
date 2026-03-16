<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Apache/PHP setups may not always expose Authorization in HTTP_AUTHORIZATION.
 */
function resolveAuthorizationHeader(): ?string
{
    $direct = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (is_string($direct) && $direct !== '') {
        return $direct;
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if (is_string($auth) && $auth !== '') {
                return $auth;
            }
        }
    }

    return null;
}

/**
 * @param array{status:int,body:mixed,headers?:array<string,string>} $response
 */
function emitResponse(array $response): void
{
    $headers = $response['headers'] ?? ['Content-Type' => 'application/json'];
    foreach ($headers as $name => $value) {
        header(sprintf('%s: %s', $name, $value));
    }

    http_response_code($response['status']);

    $body = $response['body'];
    if (is_array($body) || is_object($body)) {
        echo json_encode($body);
        return;
    }

    echo (string) $body;
}

try {
    $services = require __DIR__ . '/bootstrap.php';

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $rawInput = file_get_contents('php://input');
    $payload = $rawInput !== false && $rawInput !== '' ? json_decode($rawInput, true) : [];

    if (!is_array($payload)) {
        $payload = [];
    }

    $systemRoutes = require __DIR__ . '/routes/system.php';
    $authRoutes = require __DIR__ . '/routes/auth.php';
    $postRoutes = require __DIR__ . '/routes/posts.php';
    $graphQlRoutes = require __DIR__ . '/routes/graphql.php';

    $response = $systemRoutes($method, $path);
    if ($response !== null) {
        emitResponse($response);
        exit;
    }

    $response = $authRoutes($method, $path, $payload, $services, 'resolveAuthorizationHeader');
    if ($response !== null) {
        emitResponse($response);
        exit;
    }

    $response = $postRoutes($method, $path, $payload, $services, 'resolveAuthorizationHeader');
    if ($response !== null) {
        emitResponse($response);
        exit;
    }

    $response = $graphQlRoutes($method, $path, $payload, $services, 'resolveAuthorizationHeader');
    if ($response !== null) {
        emitResponse($response);
        exit;
    }

    emitResponse([
        'status' => 404,
        'body' => ['error' => 'Route not found.'],
        'headers' => ['Content-Type' => 'application/json'],
    ]);
} catch (Throwable) {
    emitResponse([
        'status' => 500,
        'body' => ['error' => 'Server error.'],
        'headers' => ['Content-Type' => 'application/json'],
    ]);
}