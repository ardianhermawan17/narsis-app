<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\GraphQL\Logging\GraphQlRequestLogRepositoryInterface;

final class PgGraphQlRequestLogRepository implements GraphQlRequestLogRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

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
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO graphql_request_log (id, path, root_fields, variables_summary, user_id, created_at) '
            . 'VALUES (:id, :path, :root_fields, :variables_summary, :user_id, :created_at)'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare graphql_request_log insert statement.');
        }

        $variablesJson = $variablesSummary !== null
            ? json_encode($variablesSummary, JSON_THROW_ON_ERROR)
            : null;

        $stmt->bindValue(':id', $id, \PDO::PARAM_STR);
        $stmt->bindValue(':path', $path, \PDO::PARAM_STR);
        $stmt->bindValue(':root_fields', $rootFields, \PDO::PARAM_STR);
        if ($variablesJson !== null) {
            $stmt->bindValue(':variables_summary', $variablesJson, \PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':variables_summary', null, \PDO::PARAM_NULL);
        }
        if ($userId !== null) {
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':user_id', null, \PDO::PARAM_NULL);
        }
        $stmt->bindValue(':created_at', $createdAt->format('Y-m-d H:i:sP'), \PDO::PARAM_STR);
        $stmt->execute();
    }
}
