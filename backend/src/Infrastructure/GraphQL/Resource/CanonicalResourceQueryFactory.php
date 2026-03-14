<?php

declare(strict_types=1);

namespace App\Infrastructure\GraphQL\Resource;

final class CanonicalResourceQueryFactory
{
    public function create(string $resourceName): string
    {
        $resource = preg_replace('/[^a-zA-Z0-9_]/', '', $resourceName);
        if ($resource === null || $resource === '') {
            throw new \RuntimeException('Invalid resource name.');
        }

        return sprintf('query ResourceGateway { %s { id } }', $resource);
    }
}
