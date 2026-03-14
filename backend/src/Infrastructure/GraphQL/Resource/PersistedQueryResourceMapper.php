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
            'users' => 'query UsersGateway { users { id username email } }',
            'posts' => 'query PostsGateway { posts { id userId caption visibility createdAt updatedAt images { id storageKey mimeType width height sizeBytes altText isPrimary createdAt } } }',
            'comments' => 'query CommentsGateway { comments { id content createdAt } }',
            'likes' => 'query LikesGateway { likes { id postId userId } }',
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
