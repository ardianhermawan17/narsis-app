<?php

declare(strict_types=1);

namespace App\Application\GraphQL\Resource;

interface ResourceQueryMapperInterface
{
    public function resolveQuery(string $resourceName): string;

    /**
     * @return array<string, string>
     */
    public function persistedQueries(): array;
}
