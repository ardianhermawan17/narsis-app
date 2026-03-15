<?php

declare(strict_types=1);

namespace Tests;

use App\Infrastructure\Image\Copyright\ImageProcessingWorker;
use PHPUnit\Framework\TestCase;

final class PostsTest extends TestCase
{
    private function graphqlUrl(): string
    {
        $env = getenv('GRAPHQL_TEST_URL');

        return is_string($env) && $env !== '' ? $env : 'http://localhost:8080/graphql';
    }

    private function resourceUrl(string $resource): string
    {
        $graphqlUrl = $this->graphqlUrl();
        $parts = parse_url($graphqlUrl);

        $scheme = (string) ($parts['scheme'] ?? 'http');
        $host = (string) ($parts['host'] ?? 'localhost');
        $port = isset($parts['port']) ? ':' . (string) $parts['port'] : '';

        return sprintf('%s://%s%s/v1/%s', $scheme, $host, $port, $resource);
    }

    /**
     * @param array<string, mixed>|null $variables
     * @return array<string, mixed>
     */
    private function postJson(string $url, string $query, ?array $variables = null, ?string $bearerToken = null): array
    {
        $body = (string) json_encode(['query' => $query, 'variables' => $variables], JSON_THROW_ON_ERROR);
        $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";

        if ($bearerToken !== null) {
            $headers .= "Authorization: Bearer {$bearerToken}\r\n";
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $this->markTestSkipped('GraphQL endpoint unreachable: ' . $url);
        }

        return (array) json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    private function skipIfEndpointUnreachable(): void
    {
        $parts = parse_url($this->graphqlUrl());
        $host = (string) ($parts['host'] ?? 'localhost');
        $port = (int) ($parts['port'] ?? 80);

        $conn = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($conn === false) {
            $this->markTestSkipped("Backend not reachable at {$host}:{$port}.");
        }

        fclose($conn);
    }

    private function assertNoGraphqlErrors(array $result): void
    {
        if (!empty($result['errors'])) {
            $messages = array_column($result['errors'], 'message');
            self::fail('Unexpected GraphQL errors: ' . implode('; ', $messages));
        }
    }

    public function testImageProcessingWorkerGenerates2048BitFingerprint(): void
    {
        $worker = new ImageProcessingWorker();
        $fingerprint = $worker->generateFingerprint('sample-image-content');

        self::assertSame('pdq', $fingerprint['algorithm']);
        self::assertSame(256, $fingerprint['hashBytes']);
        self::assertSame(256, strlen($fingerprint['hashValue']));
        self::assertSame(5, $fingerprint['distanceThreshold']);
    }

    public function testCreatePostWithImageViaGraphQlAndReadViaResourceRoute(): void
    {
        $this->skipIfEndpointUnreachable();

        $login = $this->postJson(
            $this->graphqlUrl(),
            'mutation { login(usernameOrEmail: "seeduser", password: "password") { accessToken } }'
        );

        $this->assertNoGraphqlErrors($login);
        $accessToken = $login['data']['login']['accessToken'] ?? '';
        if ($accessToken === '') {
            $this->markTestSkipped('Could not obtain access token for post creation test.');
        }

        // 1x1 transparent PNG
        $tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7WfY8AAAAASUVORK5CYII=';

        $create = $this->postJson(
            $this->graphqlUrl(),
            'mutation CreatePost($images:[PostImageInput!]!) { createPost(caption: "hello image", visibility: "public", images: $images) { id userId caption images { id storageKey mimeType sizeBytes } } }',
            [
                'images' => [[
                    'imageBase64' => $tinyPngBase64,
                    'mimeType' => 'image/png',
                    'altText' => 'tiny image',
                    'isPrimary' => true,
                ]],
            ],
            $accessToken
        );

        $this->assertNoGraphqlErrors($create);
        self::assertNotEmpty($create['data']['createPost']['id'] ?? null);
        self::assertNotEmpty($create['data']['createPost']['images'][0]['id'] ?? null);
        self::assertNotEmpty($create['data']['createPost']['images'][0]['storageKey'] ?? null);

        $posts = $this->postJson($this->resourceUrl('post'), '{}');
        $this->assertNoGraphqlErrors($posts);
        self::assertIsArray($posts['data']['allPost'] ?? null);
        self::assertNotEmpty($posts['data']['allPost']);
    }

    public function testGraphQLUserPostWithoutAuthReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->postJson(
            $this->graphqlUrl(),
            'query { userPost { id } }'
        );

        self::assertNotEmpty($result['errors'] ?? [], 'userPost without auth must return GraphQL errors.');
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? '',
            'userPost must require auth token.'
        );
    }

    public function testV1UserPostRouteWithAuthReturnsUserScopedPostList(): void
    {
        $this->skipIfEndpointUnreachable();

        $login = $this->postJson(
            $this->graphqlUrl(),
            'mutation { login(usernameOrEmail: "seeduser", password: "password") { accessToken } }'
        );

        $this->assertNoGraphqlErrors($login);
        $accessToken = $login['data']['login']['accessToken'] ?? '';
        if ($accessToken === '') {
            $this->markTestSkipped('Could not obtain access token for user post route test.');
        }

        $result = $this->postJson($this->resourceUrl('user-post'), '{}', null, $accessToken);
        $this->assertNoGraphqlErrors($result);
        self::assertIsArray($result['data']['userPost'] ?? null, '/v1/user-post must return userPost array.');
    }

    public function testPostCountersQueryAndResourceRouteReturnCounterFields(): void
    {
        $this->skipIfEndpointUnreachable();

        $queryResult = $this->postJson($this->graphqlUrl(), 'query { postCounters(limit: 5) { postId likesCount commentsCount sharesCount } }');
        $this->assertNoGraphqlErrors($queryResult);
        self::assertIsArray($queryResult['data']['postCounters'] ?? null);

        $routeResult = $this->postJson($this->resourceUrl('post-counters'), '{}');
        $this->assertNoGraphqlErrors($routeResult);
        self::assertIsArray($routeResult['data']['postCounters'] ?? null);

        if (!empty($routeResult['data']['postCounters'])) {
            self::assertArrayHasKey('postId', $routeResult['data']['postCounters'][0]);
            self::assertArrayHasKey('likesCount', $routeResult['data']['postCounters'][0]);
            self::assertArrayHasKey('commentsCount', $routeResult['data']['postCounters'][0]);
            self::assertArrayHasKey('sharesCount', $routeResult['data']['postCounters'][0]);
        }
    }
}
