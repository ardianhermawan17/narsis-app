<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Command\LikePost\LikePostCommand;
use App\Application\Command\LikePost\LikePostHandler;
use App\Application\Command\UnlikePost\UnlikePostCommand;
use App\Application\Command\UnlikePost\UnlikePostHandler;
use App\Application\Exception\AlreadyLikedException;
use App\Application\Exception\NotLikedException;
use App\Application\Exception\ValidationException;
use App\Domain\Feed\Repository\UserFeedRepositoryInterface;
use App\Domain\Like\Repository\LikeRepositoryInterface;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Infrastructure\ID\SnowflakeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * LikesTest — two distinct testing layers:
 *
 *  GROUP 1  Logic (Inbound Tests)
 *           Pure unit tests. No database, no HTTP.
 *           Run standalone: vendor/bin/phpunit tests/LikesTest.php
 *
 *  GROUP 2  GraphQL / HTTP (Outbound Tests)
 *           Integration tests that hit the live endpoints.
 *           Auto-skipped when backend container is unreachable.
 *           Set GRAPHQL_TEST_URL env variable to override default.
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * MANUAL TEST REFERENCE  (Postman / curl compatible)
 * ──────────────────────────────────────────────────────────────────────────────
 * Base GraphQL endpoint:   POST http://localhost:8080/graphql
 * Resource gateway (like): POST http://localhost:8080/v1/like
 *
 * STEP 1 — Login (get access token):
 *   POST /graphql
 *   Body: {
 *     "query": "mutation { login(usernameOrEmail: \"seeduser\", password: \"password\") { accessToken } }"
 *   }
 *
 * STEP 2 — Create a post (then copy the returned id for like tests):
 *   POST /graphql
 *   Header: Authorization: Bearer <accessToken>
 *   Body: {
 *     "query": "mutation CreatePost($images:[PostImageInput!]!) { createPost(caption: \"test\", visibility: \"public\", images: $images) { id } }",
 *     "variables": { "images": [{ "imageBase64": "<base64>", "mimeType": "image/png", "isPrimary": true }] }
 *   }
 *
 * STEP 3 — Like a post:
 *   POST /graphql
 *   Header: Authorization: Bearer <accessToken>
 *   Body: {
 *     "query": "mutation LikePost($postId:String!){ likePost(postId:$postId){ id likesCount } }",
 *     "variables": { "postId": "<post-id>" }
 *   }
 *   Expected: 200, data.likePost.likesCount >= 1
 *
 * STEP 4 — Like same post again (duplicate check):
 *   Same body as STEP 3.
 *   Expected: 400, errors[0].message = "You already liked this post."
 *
 * STEP 5 — Like without auth header:
 *   No Authorization header.
 *   Expected: 400, errors[0].message contains "Missing or invalid Authorization header"
 *
 * STEP 6 — Like with non-existent postId:
 *   Header: Authorization: Bearer <accessToken>
 *   Body: { "query": "mutation { likePost(postId: \"0000000000000001\") { id } }" }
 *   Expected: 400, errors[0].message = "Post not found."
 *
 * STEP 7 — Get like-domain list with likesCount via resource route:
 *   POST http://localhost:8080/v1/like
 *   Body: {}
 *   Expected: 200, data.like[].likesCount present
 *
 * STEP 8 — Unlike a post:
 *   POST /graphql
 *   Header: Authorization: Bearer <accessToken>
 *   Body: {
 *     "query": "mutation UnlikePost($postId:String!){ unlikePost(postId:$postId){ id likesCount } }",
 *     "variables": { "postId": "<post-id>" }
 *   }
 *   Expected: 200, data.unlikePost.likesCount decreases by 1 (min 0)
 */
final class LikesTest extends TestCase
{
    // =========================================================================
    // Shared helpers
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    private function makePostFixture(string $id = 'post-test-001', int $likesCount = 0): array
    {
        return [
            'id' => $id,
            'userId' => 'user-test-001',
            'caption' => 'A test post for likes',
            'visibility' => 'public',
            'likesCount' => $likesCount,
            'createdAt' => '2026-01-01T00:00:00+00:00',
            'updatedAt' => '2026-01-01T00:00:00+00:00',
            'images' => [],
        ];
    }

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

    /** Login as seeduser and return an access token, or skip if unavailable. */
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

    /**
     * Create a dedicated test post and return its id.
     * Each call returns a new post (new snowflake id), so duplicate-like tests are safe to run repeatedly.
     */
    private function createTestPost(string $accessToken): string
    {
        // 1×1 transparent PNG (minimal valid file used for testing)
        $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7WfY8AAAAASUVORK5CYII=';

        $result = $this->graphqlPost(
            'mutation CreatePost($images:[PostImageInput!]!) {
                createPost(caption: "likes-test-post", visibility: "public", images: $images) { id }
            }',
            $accessToken,
            ['images' => [[
                'imageBase64' => $tinyPng,
                'mimeType' => 'image/png',
                'altText' => 'test image for likes test',
                'isPrimary' => true,
            ]]]
        );

        $postId = $result['data']['createPost']['id'] ?? '';

        if ($postId === '') {
            $this->markTestSkipped('Could not create test post. Ensure seed and backend are ready.');
        }

        return $postId;
    }

    // =========================================================================
    // GROUP 1 — Logic (Inbound Tests)
    // Pure unit tests — no database, no HTTP. Run without Docker.
    // =========================================================================

    /**
     * Inbound 1: LikePostCommand is a value object that preserves postId and userId.
     */
    public function testLikePostCommandHoldsPostIdAndUserId(): void
    {
        $cmd = new LikePostCommand('post-abc-123', 'user-xyz-456');

        self::assertSame('post-abc-123', $cmd->postId, 'LikePostCommand must expose the postId property.');
        self::assertSame('user-xyz-456', $cmd->userId, 'LikePostCommand must expose the userId property.');
    }

    /**
     * Inbound 2: handler executes the full success path — calls incrementPostLikes,
     * upserts the feed entry, commits the transaction, and returns the updated post.
     */
    public function testLikePostHandlerReturnsUpdatedPostOnSuccess(): void
    {
        $postId = 'post-001';
        $userId = 'user-001';
        $initialPost = $this->makePostFixture($postId, likesCount: 0);
        $updatedPost = $this->makePostFixture($postId, likesCount: 1);

        $postRepo = $this->createMock(PostRepositoryInterface::class);
        $postRepo->method('findByIdWithImages')
            ->willReturnOnConsecutiveCalls($initialPost, $updatedPost);

        $likeRepo = $this->createMock(LikeRepositoryInterface::class);
        $likeRepo->method('createLike')->willReturn(true);
        $likeRepo->expects(self::once())->method('incrementPostLikes');

        $feedRepo = $this->createMock(UserFeedRepositoryInterface::class);
        $feedRepo->expects(self::once())->method('upsertPostForUser');

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(false);
        $pdo->expects(self::once())->method('commit')->willReturn(true);

        $handler = new LikePostHandler($likeRepo, $postRepo, $feedRepo, new SnowflakeGenerator(1), $pdo);
        $result = $handler->handle(new LikePostCommand($postId, $userId));

        self::assertSame($postId, $result['id'], 'Handler should return the liked post id.');
        self::assertSame(1, $result['likesCount'], 'Handler should return the incremented likesCount.');
        self::assertArrayHasKey('images', $result, 'Handler response should include images array.');
    }

    /**
     * Inbound 3: handler throws ValidationException when postId trims to an empty string.
     */
    public function testLikePostHandlerRejectsEmptyPostId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/postId is required/i');

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects(self::never())->method('beginTransaction');

        $handler = new LikePostHandler(
            $this->createMock(LikeRepositoryInterface::class),
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(UserFeedRepositoryInterface::class),
            new SnowflakeGenerator(1),
            $pdo
        );

        $handler->handle(new LikePostCommand('   ', 'user-001'));
    }

    /**
     * Inbound 4: handler throws ValidationException when the post does not exist.
     * No transaction should be started when the post is absent.
     */
    public function testLikePostHandlerRejectsNonExistentPost(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Post not found/i');

        $postRepo = $this->createMock(PostRepositoryInterface::class);
        $postRepo->method('findByIdWithImages')->willReturn(null);

        $pdo = $this->createMock(\PDO::class);
        $pdo->expects(self::never())->method('beginTransaction');

        $handler = new LikePostHandler(
            $this->createMock(LikeRepositoryInterface::class),
            $postRepo,
            $this->createMock(UserFeedRepositoryInterface::class),
            new SnowflakeGenerator(1),
            $pdo
        );

        $handler->handle(new LikePostCommand('nonexistent-post-id', 'user-001'));
    }

    /**
     * Inbound 5: handler throws AlreadyLikedException when createLike returns false (duplicate row).
     * The transaction must be rolled back; commit must NOT be called;
     * incrementPostLikes and upsertPostForUser must NOT be called.
     */
    public function testLikePostHandlerThrowsAlreadyLikedOnDuplicate(): void
    {
        $this->expectException(AlreadyLikedException::class);
        $this->expectExceptionMessageMatches('/already liked/i');

        $postRepo = $this->createMock(PostRepositoryInterface::class);
        $postRepo->method('findByIdWithImages')->willReturn($this->makePostFixture('post-002'));

        $likeRepo = $this->createMock(LikeRepositoryInterface::class);
        $likeRepo->method('createLike')->willReturn(false);
        $likeRepo->expects(self::never())->method('incrementPostLikes');

        $feedRepo = $this->createMock(UserFeedRepositoryInterface::class);
        $feedRepo->expects(self::never())->method('upsertPostForUser');

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);

        $handler = new LikePostHandler($likeRepo, $postRepo, $feedRepo, new SnowflakeGenerator(1), $pdo);
        $handler->handle(new LikePostCommand('post-002', 'user-001'));
    }

    /**
     * Inbound 6: AlreadyLikedException carries the correct user-facing message and 409 HTTP status code.
     */
    public function testAlreadyLikedExceptionHasCorrectMessageAndStatusCode(): void
    {
        $exception = new AlreadyLikedException();

        self::assertSame(
            'You already liked this post.',
            $exception->getMessage(),
            'AlreadyLikedException message must match the API contract.'
        );
        self::assertSame(
            409,
            $exception->statusCode(),
            'AlreadyLikedException HTTP status must be 409 Conflict.'
        );
    }

    /**
     * Inbound 7: unlike handler executes success path and decrements likes counter.
     */
    public function testUnlikePostHandlerReturnsUpdatedPostOnSuccess(): void
    {
        $postId = 'post-003';
        $userId = 'user-001';
        $initialPost = $this->makePostFixture($postId, likesCount: 1);
        $updatedPost = $this->makePostFixture($postId, likesCount: 0);

        $postRepo = $this->createMock(PostRepositoryInterface::class);
        $postRepo->method('findByIdWithImages')
            ->willReturnOnConsecutiveCalls($initialPost, $updatedPost);

        $likeRepo = $this->createMock(LikeRepositoryInterface::class);
        $likeRepo->method('deleteLike')->willReturn(true);
        $likeRepo->expects(self::once())->method('decrementPostLikes');

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(false);
        $pdo->expects(self::once())->method('commit')->willReturn(true);

        $handler = new UnlikePostHandler($likeRepo, $postRepo, $pdo);
        $result = $handler->handle(new UnlikePostCommand($postId, $userId));

        self::assertSame($postId, $result['id'], 'Handler should return the unliked post id.');
        self::assertSame(0, $result['likesCount'], 'Handler should return decremented likesCount.');
    }

    /**
     * Inbound 8: unlike handler throws NotLikedException if no like row exists.
     */
    public function testUnlikePostHandlerThrowsNotLikedWhenLikeDoesNotExist(): void
    {
        $this->expectException(NotLikedException::class);
        $this->expectExceptionMessageMatches('/not liked/i');

        $postRepo = $this->createMock(PostRepositoryInterface::class);
        $postRepo->method('findByIdWithImages')->willReturn($this->makePostFixture('post-004', likesCount: 0));

        $likeRepo = $this->createMock(LikeRepositoryInterface::class);
        $likeRepo->method('deleteLike')->willReturn(false);
        $likeRepo->expects(self::never())->method('decrementPostLikes');

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $pdo->expects(self::never())->method('commit');

        $handler = new UnlikePostHandler($likeRepo, $postRepo, $pdo);
        $handler->handle(new UnlikePostCommand('post-004', 'user-001'));
    }

    // =========================================================================
    // GROUP 2 — GraphQL / HTTP (Outbound Tests)
    // Hits live endpoints. Auto-skipped when backend is unreachable.
    // Covers three surfaces:
    //   a) Base GraphQL  POST /graphql
    //   b) Resource route POST /v1/like
    //   c) like-domain mutations/query via /graphql and /v1/like
    // =========================================================================

    /**
     * Outbound 1: likePost mutation via base /graphql returns the updated Post object.
     * Creates a fresh post each run to ensure a clean like state.
     */
    public function testGraphQLLikePostMutationReturnsUpdatedPost(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $postId = $this->createTestPost($token);

        $result = $this->graphqlPost(
            'mutation LikePost($postId:String!){
                likePost(postId:$postId){
                    id userId caption visibility likesCount createdAt updatedAt images { id }
                }
            }',
            $token,
            ['postId' => $postId]
        );

        $this->assertNoGraphqlErrors($result);
        $likedPost = $result['data']['likePost'] ?? null;

        self::assertIsArray($likedPost, 'likePost mutation must return a Post object.');
        self::assertSame($postId, $likedPost['id'], 'likePost must return the id of the post that was liked.');
        self::assertArrayHasKey('userId', $likedPost, 'likePost response must include userId.');
        self::assertArrayHasKey('likesCount', $likedPost, 'likePost response must include likesCount.');
        self::assertArrayHasKey('images', $likedPost, 'likePost response must include images array.');
        self::assertGreaterThanOrEqual(1, $likedPost['likesCount'], 'likesCount must be at least 1 after a like.');
    }

    /**
    * Outbound 2: likesCount increments by exactly 1 after one likePost call,
    * as confirmed by reading the post back via the allPost query.
     */
    public function testGraphQLLikePostIncrementsLikesCountByOne(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $postId = $this->createTestPost($token);

        // Read initial likesCount for the new post (should be 0)
        $beforeResult = $this->graphqlPost(
            'query AllPost($limit:Int){ allPost(limit:$limit){ id likesCount } }',
            $token,
            ['limit' => 100]
        );
        $this->assertNoGraphqlErrors($beforeResult);
        $beforeCount = 0;
        foreach ($beforeResult['data']['allPost'] ?? [] as $post) {
            if ($post['id'] === $postId) {
                $beforeCount = (int) ($post['likesCount'] ?? 0);
                break;
            }
        }

        // Like the post
        $likeResult = $this->graphqlPost(
            'mutation LikePost($postId:String!){ likePost(postId:$postId){ id likesCount } }',
            $token,
            ['postId' => $postId]
        );
        $this->assertNoGraphqlErrors($likeResult);
        $afterCount = (int) ($likeResult['data']['likePost']['likesCount'] ?? -1);

        self::assertSame(
            $beforeCount + 1,
            $afterCount,
            'likesCount must increment by exactly 1 after a single likePost call.'
        );
    }

    /**
     * Outbound 3: likePost without an Authorization header must be rejected with an auth error.
     */
    public function testGraphQLLikePostWithoutAuthReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->graphqlPost(
            'mutation { likePost(postId: "0000000000000001") { id } }'
            // no bearer token passed
        );

        self::assertNotEmpty($result['errors'] ?? [], 'likePost without auth must return a GraphQL error array.');
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? '',
            'Auth error must reference the missing Authorization header.'
        );
    }

    /**
     * Outbound 4: likePost with a non-existent postId must return a "Post not found" error.
     */
    public function testGraphQLLikePostWithInvalidPostIdReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();

        $result = $this->graphqlPost(
            'mutation LikePost($postId:String!){ likePost(postId:$postId){ id } }',
            $token,
            ['postId' => '0000000000000001']
        );

        self::assertNotEmpty($result['errors'] ?? [], 'likePost with invalid postId must return GraphQL errors.');
        self::assertStringContainsString(
            'Post not found',
            $result['errors'][0]['message'] ?? '',
            'Error message must indicate the post was not found.'
        );
    }

    /**
     * Outbound 5: liking the same post a second time with the same user must return
     * an AlreadyLiked error and must NOT increment the counter again.
     */
    public function testGraphQLLikePostTwiceReturnsAlreadyLikedError(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $postId = $this->createTestPost($token);

        $likeQuery = 'mutation LikePost($postId:String!){ likePost(postId:$postId){ id likesCount } }';

        // First like — must succeed
        $first = $this->graphqlPost($likeQuery, $token, ['postId' => $postId]);
        $this->assertNoGraphqlErrors($first);
        $countAfterFirst = (int) ($first['data']['likePost']['likesCount'] ?? 0);

        // Second like — same user, same post, must fail
        $second = $this->graphqlPost($likeQuery, $token, ['postId' => $postId]);

        self::assertNotEmpty($second['errors'] ?? [], 'Second like on same post must return a GraphQL error.');
        self::assertStringContainsString(
            'already liked',
            strtolower($second['errors'][0]['message'] ?? ''),
            'Error must communicate that the post was already liked.'
        );

        // Verify the counter was not incremented by the duplicate
        $countersResult = $this->graphqlPost(
            'query AllPost($limit:Int){ allPost(limit:$limit){ id likesCount } }',
            $token,
            ['limit' => 100]
        );
        $this->assertNoGraphqlErrors($countersResult);
        $countAfterDuplicate = $countAfterFirst;
        foreach ($countersResult['data']['allPost'] ?? [] as $post) {
            if ($post['id'] === $postId) {
                $countAfterDuplicate = (int) ($post['likesCount'] ?? 0);
                break;
            }
        }
        self::assertSame(
            $countAfterFirst,
            $countAfterDuplicate,
            'likesCount must not change when a duplicate like is attempted.'
        );
    }

    /**
     * Outbound 6: POST /v1/like resource route returns the current user's liked posts.
     * This tests the persisted-query mapping for the resource gateway.
     */
    public function testV1LikeResourceRouteReturnsUserLikeListWithLikesCountField(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $result = $this->gatewayPost($this->resourceUrl('like'), '{}', $token);
        $this->assertNoGraphqlErrors($result);

        $posts = $result['data']['userLike'] ?? null;
        self::assertIsArray($posts, 'POST /v1/like must return a like array.');

        if (!empty($posts)) {
            self::assertArrayHasKey(
                'likesCount',
                $posts[0],
                'Each item returned from /v1/like must include the likesCount field.'
            );
            self::assertArrayHasKey('id', $posts[0], 'Each item from /v1/like must include id.');
            self::assertArrayHasKey('userId', $posts[0], 'Each item from /v1/like must include userId.');
        }
    }

    /**
     * Outbound 7: unlikePost mutation decrements likesCount after a successful like.
     */
    public function testGraphQLUnlikePostDecrementsLikesCountByOne(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $postId = $this->createTestPost($token);

        $likeResult = $this->graphqlPost(
            'mutation LikePost($postId:String!){ likePost(postId:$postId){ id likesCount } }',
            $token,
            ['postId' => $postId]
        );
        $this->assertNoGraphqlErrors($likeResult);
        $countAfterLike = (int) ($likeResult['data']['likePost']['likesCount'] ?? 0);

        $unlikeResult = $this->graphqlPost(
            'mutation UnlikePost($postId:String!){ unlikePost(postId:$postId){ id likesCount } }',
            $token,
            ['postId' => $postId]
        );
        $this->assertNoGraphqlErrors($unlikeResult);
        $countAfterUnlike = (int) ($unlikeResult['data']['unlikePost']['likesCount'] ?? -1);

        self::assertSame(
            max(0, $countAfterLike - 1),
            $countAfterUnlike,
            'unlikePost must decrement likesCount by one and never below 0.'
        );
    }

    public function testGraphQLUserLikeWithoutAuthReturnsError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->graphqlPost('query { userLike { id } }');

        self::assertNotEmpty($result['errors'] ?? [], 'userLike without auth must return GraphQL errors.');
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? '',
            'userLike must require auth token.'
        );
    }

    public function testV1UserLikeRouteWithAuthReturnsUserLikeArray(): void
    {
        $this->skipIfEndpointUnreachable();

        $token = $this->loginAsSeedUser();
        $result = $this->gatewayPost($this->resourceUrl('user-like'), '{}', $token);

        $this->assertNoGraphqlErrors($result);
        self::assertIsArray($result['data']['userLike'] ?? null, '/v1/user-like must return userLike array.');
    }
}
