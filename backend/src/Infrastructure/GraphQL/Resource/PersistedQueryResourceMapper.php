<?php

declare(strict_types=1);

namespace App\Infrastructure\GraphQL\Resource;

use App\Application\GraphQL\Resource\ResourceQueryMapperInterface;

final class PersistedQueryResourceMapper implements ResourceQueryMapperInterface
{
    /** @var array<string, string> */
    private array $queryMap;

    public function __construct(private readonly CanonicalResourceQueryFactory $canonicalFactory)
    {
        $this->queryMap = [
            // Example persisted queries for /v1/<resource> mapping.
            'auth' => 'query AuthGateway { me { id username email displayName bio createdAt updatedAt } }',
            'user' => 'query UserGateway { me { id username email displayName bio createdAt updatedAt } }',
            'post' => 'query PostGateway { allPost { id userId caption visibility likesCount createdAt updatedAt images { id storageKey mimeType width height sizeBytes altText isPrimary createdAt } } }',
            'user-post' => 'query UserPostGateway { userPost { id userId caption visibility likesCount createdAt updatedAt images { id storageKey mimeType width height sizeBytes altText isPrimary createdAt } } }',
            'comment' => 'query CommentGateway { comment { id content createdAt } }',
            'like' => 'query LikeGateway { like { id userId caption visibility likesCount createdAt updatedAt } }',
            'user-like' => 'query UserLikeGateway { userLike { id userId caption visibility likesCount createdAt updatedAt } }',
            'feed' => 'query FeedGateway { myFeed { id userId caption visibility likesCount createdAt updatedAt images { id storageKey mimeType width height sizeBytes altText isPrimary createdAt } } }',
            'profile' => 'query ProfileGateway { me { id username email displayName bio createdAt updatedAt } }',
        ];
    }

    public function resolveQuery(string $resourceName): string
    {
        $key = strtolower(trim($resourceName));

        if (isset($this->queryMap[$key])) {
            return $this->queryMap[$key];
        }

        return $this->canonicalFactory->create($key);
    }

    /**
     * @return array<string, string>
     */
    public function persistedQueries(): array
    {
        return $this->queryMap;
    }
}