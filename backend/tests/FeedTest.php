<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * FeedTest — dedicated outbound tests for the feed domain schema and /v1/feed route.
 */
final class FeedTest extends TestCase
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
    private function graphqlPost(string $query, ?string $bearerToken = null, ?array $variables = null): array
    {
        return $this->gatewayPost($this->graphqlUrl(), $query, $bearerToken, $variables);
    }

    /**
     * @param array<string, mixed>|null $variables
     * @return array<string, mixed>
     */
    private function gatewayPost(string $url, string $query, ?string $bearerToken = null, ?array $variables = null): array
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
            $this->markTestSkipped(
                "Backend not reachable at {$host}:{$port}. " .
                'Start containers and apply seed, or set GRAPHQL_TEST_URL.'
            );
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

    private function loginAsSeedUser(): string
    {
        $result = $this->graphqlPost(
            'mutation { login(usernameOrEmail: "seeduser", password: "password") { accessToken } }'
        );
        $token = $result['data']['login']['accessToken'] ?? '';

        if ($token === '') {
            $this->markTestSkipped('Could not obtain access token from seed account. Ensure seed is applied.');
        }

        return $token;
    }

    private function createTestPost(string $accessToken): string
    {
        $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7WfY8AAAAASUVORK5CYII=';

        $result = $this->graphqlPost(
            'mutation CreatePost($images:[PostImageInput!]!) {
                createPost(caption: "feed-test-post", visibility: "public", images: $images) { id }
            }',
            $accessToken,
            ['images' => [[
                'imageBase64' => $tinyPng,
                'mimeType' => 'image/png',
                'altText' => 'feed test image',
                'isPrimary' => true,
            ]]]
        );

        $postId = $result['data']['createPost']['id'] ?? '';

        if ($postId === '') {
            $this->markTestSkipped('Could not create test post. Ensure seed and backend are ready.');
        }

        return $postId;
    }

    public function testGraphQLMyFeedWithAuthReturnsFeedArray(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();

        $result = $this->graphqlPost(
            'query Feed($limit:Int){
                myFeed(limit:$limit){
                    id userId caption visibility likesCount createdAt updatedAt
                    images { id storageKey isPrimary }
                }
            }',
            $token,
            ['limit' => 10]
        );

        $this->assertNoGraphqlErrors($result);
        $feed = $result['data']['myFeed'] ?? null;
        self::assertIsArray($feed, 'myFeed must return an array for authenticated user.');
    }

    public function testGraphQLMyFeedWithoutAuthReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->graphqlPost('query { myFeed { id } }');

        self::assertNotEmpty($result['errors'] ?? [], 'myFeed without auth must return GraphQL errors.');
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? '',
            'myFeed must require valid Authorization header.'
        );
    }

    public function testV1FeedRouteWithAuthReturnsFeedArray(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $result = $this->gatewayPost($this->resourceUrl('feed'), '{}', $token);

        $this->assertNoGraphqlErrors($result);
        self::assertIsArray($result['data']['myFeed'] ?? null, '/v1/feed must return myFeed array.');
    }

    public function testV1FeedRouteWithoutAuthReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->gatewayPost($this->resourceUrl('feed'), '{}');

        self::assertNotEmpty($result['errors'] ?? [], '/v1/feed without auth should return GraphQL errors.');
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? '',
            '/v1/feed must require auth token.'
        );
    }

    public function testFeedIncludesNewlyCreatedPost(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $postId = $this->createTestPost($token);

        $result = $this->graphqlPost(
            'query Feed($limit:Int){ myFeed(limit:$limit){ id } }',
            $token,
            ['limit' => 50]
        );

        $this->assertNoGraphqlErrors($result);
        $feedIds = array_column($result['data']['myFeed'] ?? [], 'id');

        self::assertContains($postId, $feedIds, 'New post must be visible in author feed projection.');
    }
}
