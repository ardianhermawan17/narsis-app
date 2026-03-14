<?php

declare(strict_types=1);

use GraphQL\GraphQL;

return static function (string $method, string $path, array $payload, array $services, callable $resolveAuthorizationHeader): ?array {
    if (!($method === 'POST' && ($path === '/graphql' || preg_match('#^/v1/([a-zA-Z0-9_\-]+)$#', $path) === 1))) {
        return null;
    }

    $schemaRegistry = $services['graphQlSchemaRegistry'];
    $graphQlGatewayAdapter = $services['graphQlGatewayAdapter'];
    $graphQlTransportErrorHandler = $services['graphQlTransportErrorHandler'];

    try {
        $adapted = $graphQlGatewayAdapter->adapt($path, $payload);

        $routeResource = null;
        if (preg_match('#^/v1/([a-zA-Z0-9_\-]+)$#', $path, $matches) === 1 && isset($matches[1])) {
            $routeResource = strtolower((string) $matches[1]);
        }

        $resourceFromQuery = isset($adapted['resources'][0]) ? strtolower((string) $adapted['resources'][0]) : null;
        $schemaResource = $routeResource ?? $resourceFromQuery;
        $schema = $schemaRegistry->resolve($schemaResource);

        $result = GraphQL::executeQuery(
            $schema,
            $adapted['query'],
            null,
            [
                'authorizationHeader' => $resolveAuthorizationHeader(),
                'resources' => $adapted['resources'],
                'persistedMap' => $adapted['persistedMap'],
                'schemaResource' => $schemaResource,
            ],
            $adapted['variables'],
            $adapted['operationName']
        );

        $output = $result->toArray();

        return [
            'status' => isset($output['errors']) ? 400 : 200,
            'body' => $output,
            'headers' => ['Content-Type' => 'application/json'],
        ];
    } catch (Throwable $e) {
        $response = $graphQlTransportErrorHandler->handle($e);

        return [
            'status' => $response['status'],
            'body' => $response['body'],
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
};
