<?php

declare(strict_types=1);

namespace App\Application\GraphQL\Logging;

interface GraphQlRequestLogRepositoryInterface
{
    /**
     * @param array<string, mixed>|null $variablesSummary
     */
    public function save(
        string $id,
        string $path,
        string $rootFields,
        ?array $variablesSummary,
        ?string $userId,
        \DateTimeImmutable $createdAt
    ): void;
}