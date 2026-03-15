<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Command\AddComment\AddCommentCommand;
use App\Application\Command\AddComment\AddCommentHandler;
use App\Application\Exception\ValidationException;
use App\Application\Query\ListComments\ListCommentsQueryHandler;
use App\Domain\Comment\Comment;
use App\Domain\Comment\Repository\CommentRepositoryInterface;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Infrastructure\ID\SnowflakeGenerator;
use PHPUnit\Framework\TestCase;

final class CommentsTest extends TestCase
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

    private function loginAsSeedUser(): string
    {
        $login = $this->postJson(
            $this->graphqlUrl(),
            'mutation { login(usernameOrEmail: "seeduser", password: "password") { accessToken } }'
        );

        $this->assertNoGraphqlErrors($login);
        $accessToken = $login['data']['login']['accessToken'] ?? '';
        if ($accessToken === '') {
            $this->markTestSkipped('Could not obtain access token from seed account. Ensure seed is applied.');
        }

        return $accessToken;
    }

    private function createTestPost(string $accessToken): string
    {
        $tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7WfY8AAAAASUVORK5CYII=';

        $create = $this->postJson(
            $this->graphqlUrl(),
            'mutation CreatePost($images:[PostImageInput!]!) { createPost(caption: "comment-test-post", visibility: "public", images: $images) { id } }',
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
        $postId = $create['data']['createPost']['id'] ?? '';
        if ($postId === '') {
            $this->markTestSkipped('Could not create post for comment tests.');
        }

        return $postId;
    }

    public function testAddCommentCommandHoldsInputValues(): void
    {
        $command = new AddCommentCommand('post-123', 'user-456', 'hello world', 'parent-789');

        self::assertSame('post-123', $command->postId);
        self::assertSame('user-456', $command->userId);
        self::assertSame('hello world', $command->content);
        self::assertSame('parent-789', $command->parentCommentId);
    }

    public function testAddCommentHandlerReturnsSavedCommentPayload(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $posts = $this->createMock(PostRepositoryInterface::class);
        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['beginTransaction', 'commit', 'inTransaction'])
            ->getMock();

        $posts->expects(self::once())
            ->method('findByIdWithImages')
            ->with('post-123')
            ->willReturn(['id' => 'post-123']);

        $comments->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (Comment $comment): bool {
                self::assertSame('post-123', $comment->postId());
                self::assertSame('user-456', $comment->userId());
                self::assertSame('hello world', $comment->content());

                return $comment->id() !== '';
            }));

        $comments->expects(self::once())
            ->method('incrementPostComments')
            ->with(
                'post-123',
                self::isInstanceOf(\DateTimeImmutable::class)
            );

        $pdo->expects(self::once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo->expects(self::once())
            ->method('commit')
            ->willReturn(true);

        $pdo->expects(self::never())
            ->method('inTransaction');

        $handler = new AddCommentHandler($comments, $posts, new SnowflakeGenerator(1), $pdo);
        $result = $handler->handle(new AddCommentCommand('post-123', 'user-456', 'hello world'));

        self::assertSame('post-123', $result['postId']);
        self::assertSame('user-456', $result['userId']);
        self::assertSame('hello world', $result['content']);
        self::assertNull($result['parentCommentId']);
        self::assertNotEmpty($result['id']);
        self::assertNotEmpty($result['createdAt']);
        self::assertNotEmpty($result['updatedAt']);
    }

    public function testAddCommentHandlerRejectsEmptyPostId(): void
    {
        $handler = new AddCommentHandler(
            $this->createMock(CommentRepositoryInterface::class),
            $this->createMock(PostRepositoryInterface::class),
            new SnowflakeGenerator(1),
            $this->createMock(\PDO::class)
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('postId is required.');

        $handler->handle(new AddCommentCommand('   ', 'user-456', 'hello world'));
    }

    public function testAddCommentHandlerRejectsEmptyContent(): void
    {
        $posts = $this->createMock(PostRepositoryInterface::class);
        $posts->expects(self::never())
            ->method('findByIdWithImages');

        $handler = new AddCommentHandler(
            $this->createMock(CommentRepositoryInterface::class),
            $posts,
            new SnowflakeGenerator(1),
            $this->createMock(\PDO::class)
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('content is required.');

        $handler->handle(new AddCommentCommand('post-123', 'user-456', '   '));
    }

    public function testAddCommentHandlerRejectsMissingPost(): void
    {
        $posts = $this->createMock(PostRepositoryInterface::class);
        $posts->expects(self::once())
            ->method('findByIdWithImages')
            ->with('post-404')
            ->willReturn(null);

        $handler = new AddCommentHandler(
            $this->createMock(CommentRepositoryInterface::class),
            $posts,
            new SnowflakeGenerator(1),
            $this->createMock(\PDO::class)
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Post not found.');

        $handler->handle(new AddCommentCommand('post-404', 'user-456', 'hello world'));
    }

    public function testListCommentsQueryHandlerRequiresPostId(): void
    {
        $handler = new ListCommentsQueryHandler($this->createMock(CommentRepositoryInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('postId is required.');

        $handler->handle('   ');
    }

    public function testListCommentsQueryHandlerClampsLimitToOneHundred(): void
    {
        $comments = $this->createMock(CommentRepositoryInterface::class);
        $comments->expects(self::once())
            ->method('findByPostId')
            ->with('post-123', 100)
            ->willReturn([]);

        $handler = new ListCommentsQueryHandler($comments);
        $result = $handler->handle('post-123', 999);

        self::assertSame([], $result);
    }

    public function testAddCommentWithoutAuthReturnsGraphQlError(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->postJson(
            $this->graphqlUrl(),
            'mutation AddComment($postId:String!,$content:String!){ addComment(postId:$postId, content:$content){ id } }',
            [
                'postId' => '0000000000000001',
                'content' => 'unauthorized comment',
            ]
        );

        self::assertNotEmpty($result['errors'] ?? []);
        self::assertStringContainsString(
            'Missing or invalid Authorization header',
            $result['errors'][0]['message'] ?? ''
        );
    }

    public function testAddCommentAndReadViaCommentResourceRoute(): void
    {
        $this->skipIfEndpointUnreachable();

        $accessToken = $this->loginAsSeedUser();
        $postId = $this->createTestPost($accessToken);

        $content = 'comment body ' . (string) microtime(true);
        $added = $this->postJson(
            $this->graphqlUrl(),
            'mutation AddComment($postId:String!,$content:String!){ addComment(postId:$postId, content:$content){ id postId userId content createdAt updatedAt } }',
            [
                'postId' => $postId,
                'content' => $content,
            ],
            $accessToken
        );

        $this->assertNoGraphqlErrors($added);
        self::assertSame($postId, $added['data']['addComment']['postId'] ?? null);
        self::assertSame($content, $added['data']['addComment']['content'] ?? null);
        self::assertNotEmpty($added['data']['addComment']['id'] ?? null);

        $listed = $this->postJson(
            $this->resourceUrl('comment'),
            '{}',
            [
                'postId' => $postId,
                'limit' => 5,
            ]
        );

        $this->assertNoGraphqlErrors($listed);
        self::assertIsArray($listed['data']['comment'] ?? null);
        self::assertNotEmpty($listed['data']['comment']);
        self::assertSame($postId, $listed['data']['comment'][0]['postId'] ?? null);
        self::assertSame($content, $listed['data']['comment'][0]['content'] ?? null);
    }
}