<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Adapter;

use App\Application\GraphQL\Resource\ResourceQueryMapperInterface;
use App\Application\GraphQL\Resource\TopLevelResourceExtractor;
use App\Application\GraphQL\Validation\GraphQlDocumentLimiter;
use App\Infrastructure\GraphQL\Cache\LruAstCache;
use GraphQL\Language\Parser;

final class GraphQlResourceGatewayAdapter
{
    public function __construct(
        private readonly ResourceQueryMapperInterface $queryMapper,
        private readonly TopLevelResourceExtractor $resourceExtractor,
        private readonly GraphQlDocumentLimiter $documentLimiter,
        private readonly LruAstCache $astCache
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{query:string,variables:array<string,mixed>|null,operationName:string|null,resources:array<int,string>,persistedMap:array<string,string>}
     */
    public function adapt(string $path, array $payload): array
    {
        if (preg_match('#^/v1/([a-zA-Z0-9_\-]+)$#', $path, $matches) === 1 && isset($matches[1])) {
            $resourceName = strtolower($matches[1]);
            $currentQuery = isset($payload['query']) ? trim((string) $payload['query']) : '';
            if ($currentQuery === '' || $currentQuery === '{}') {
                $payload['query'] = $this->queryMapper->resolveQuery($resourceName);
            }
        }

        $query = isset($payload['query']) ? trim((string) $payload['query']) : '';
        if ($query === '') {
            throw new \RuntimeException('GraphQL query is required.');
        }

        $cacheKey = hash('sha256', $query);
        $document = $this->astCache->get($cacheKey);
        if ($document === null) {
            $document = Parser::parse($query);
            $this->astCache->put($cacheKey, $document);
        }

        $this->documentLimiter->assertLimits($document);

        $variables = $payload['variables'] ?? null;
        if ($variables !== null && !is_array($variables)) {
            throw new \RuntimeException('GraphQL variables must be an object.');
        }

        $operationName = $payload['operationName'] ?? null;
        if ($operationName !== null && !is_string($operationName)) {
            throw new \RuntimeException('GraphQL operationName must be a string.');
        }

        return [
            'query' => $query,
            'variables' => is_array($variables) ? $variables : null,
            'operationName' => is_string($operationName) ? $operationName : null,
            'resources' => $this->resourceExtractor->extract($document),
            'persistedMap' => $this->queryMapper->persistedQueries(),
        ];
    }
}
