<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Middleware;

use App\Application\GraphQL\Resource\ResourceQueryMapperInterface;
use App\Application\GraphQL\Resource\TopLevelResourceExtractor;
use App\Application\GraphQL\Validation\GraphQlDocumentLimiter;
use App\Infrastructure\GraphQL\Cache\LruAstCache;
use GraphQL\Language\Parser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GraphQlResourceGatewayMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResourceQueryMapperInterface $queryMapper,
        private readonly TopLevelResourceExtractor $resourceExtractor,
        private readonly GraphQlDocumentLimiter $documentLimiter,
        private readonly LruAstCache $astCache,
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($path !== '/graphql' && !preg_match('#^/v1/([a-zA-Z0-9_\-]+)$#', $path, $matches)) {
            return $handler->handle($request);
        }

        try {
            $payload = $this->parsePayload($request);

            if ($path !== '/graphql' && isset($matches[1])) {
                $resourceName = strtolower($matches[1]);
                $payload['query'] = $this->queryMapper->resolveQuery($resourceName);
            }

            $query = isset($payload['query']) ? trim((string) $payload['query']) : '';
            if ($query === '') {
                return $this->jsonError(422, 'MISSING_QUERY', 'GraphQL query is required.');
            }

            $cacheKey = hash('sha256', $query);
            $document = $this->astCache->get($cacheKey);
            if ($document === null) {
                $document = Parser::parse($query);
                $this->astCache->put($cacheKey, $document);
            }

            $this->documentLimiter->assertLimits($document);
            $resources = $this->resourceExtractor->extract($document);

            $request = $request
                ->withAttribute('graphql.query', $query)
                ->withAttribute('graphql.variables', $payload['variables'] ?? null)
                ->withAttribute('graphql.operationName', $payload['operationName'] ?? null)
                ->withAttribute('graphql.document', $document)
                ->withAttribute('graphql.resources', $resources)
                ->withAttribute('graphql.persistedMap', $this->queryMapper->persistedQueries());

            return $handler->handle($request);
        } catch (\RuntimeException $exception) {
            return $this->jsonError(400, 'GRAPHQL_GATEWAY_ERROR', $exception->getMessage());
        } catch (\Throwable) {
            return $this->jsonError(500, 'INTERNAL_SERVER_ERROR', 'GraphQL gateway failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePayload(ServerRequestInterface $request): array
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            return $parsedBody;
        }

        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON payload.');
        }

        return $decoded;
    }

    private function jsonError(int $statusCode, string $code, string $message): ResponseInterface
    {
        $payload = [
            'errors' => [
                [
                    'message' => $message,
                    'extensions' => [
                        'code' => $code,
                    ],
                ],
            ],
            'data' => null,
        ];

        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write((string) json_encode($payload));
        return $response;
    }
}
