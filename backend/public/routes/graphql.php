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
    $graphQlRequestLogger = $services['graphQlRequestLogger'];

    $authorizationHeader = $resolveAuthorizationHeader();
    $adapted = null;

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
                'authorizationHeader' => $authorizationHeader,
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
    } finally {
        try {
            $rootFields = [];
            if (is_array($adapted) && isset($adapted['resources']) && is_array($adapted['resources'])) {
                $rootFields = array_map(static fn ($value): string => (string) $value, $adapted['resources']);
            }

            if ($rootFields === [] && preg_match('#^/v1/([a-zA-Z0-9_\-]+)$#', $path, $logMatches) === 1 && isset($logMatches[1])) {
                $rootFields = [strtolower((string) $logMatches[1])];
            }

            $variables = null;
            if (is_array($adapted) && array_key_exists('variables', $adapted) && is_array($adapted['variables'])) {
                $variables = $adapted['variables'];
            } elseif (isset($payload['variables']) && is_array($payload['variables'])) {
                $variables = $payload['variables'];
            }

            $graphQlRequestLogger->log(
                $path,
                $rootFields,
                $variables,
                is_string($authorizationHeader) ? $authorizationHeader : null
            );
        } catch (Throwable) {
            // Logging is best-effort and must never break GraphQL responses.
        }
    }
};
