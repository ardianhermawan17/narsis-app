<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Command\LoginUser\LoginUserCommand;
use App\Application\Command\LoginUser\LoginUserHandler;
use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Auth\JwtProvider;
use App\Infrastructure\ID\SnowflakeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * AuthTest — two distinct testing layers:
 *
 *  GROUP 1  Logic (Inbound Tests)
 *           Pure unit tests for Domain and Application layers.
 *           No database, no HTTP. Run standalone with: vendor/bin/phpunit
 *
 *  GROUP 2  GraphQL (Outbound Tests)
 *           Integration tests that hit the live /graphql HTTP endpoint.
 *           Require the backend container to be running and seed applied.
 *           Set GRAPHQL_TEST_URL=http://localhost:8080/graphql (or default is used).
 *           Tests are auto-skipped when the endpoint is unreachable.
 */
final class AuthTest extends TestCase
{
    // =========================================================================
    // Shared helpers
    // =========================================================================

    private function makeTestUser(): User
    {
        $hash = password_hash('correct-password', PASSWORD_BCRYPT);
        $now  = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        return new User(
            '1234567890000001',
            'testuser',
            'testuser@example.com',
            (string) $hash,
            $now,
            $now,
            'Test User',
            'Test bio'
        );
    }

    private function makeJwtProvider(): JwtProvider
    {
        return new JwtProvider('unit-test-secret-key-32-chars!!', 900);
    }

    // =========================================================================
    // GROUP 1 — Logic (Inbound Tests)
    // Exercises Domain entities and Application handlers with no I/O.
    // =========================================================================

    public function testUserVerifyPasswordAcceptsCorrectPassword(): void
    {
        $user = $this->makeTestUser();
        self::assertTrue($user->verifyPassword('correct-password'), 'Password verification should pass for correct credentials.');
    }

    public function testUserVerifyPasswordRejectsWrongPassword(): void
    {
        $user = $this->makeTestUser();
        self::assertFalse($user->verifyPassword('wrong-password'), 'Password verification should fail for invalid credentials.');
    }

    public function testUserToPublicArrayContainsExpectedKeys(): void
    {
        $user = $this->makeTestUser();
        $arr  = $user->toPublicArray();

        self::assertArrayHasKey('id', $arr, 'Public user payload must contain id.');
        self::assertArrayHasKey('username', $arr, 'Public user payload must contain username.');
        self::assertArrayHasKey('email', $arr, 'Public user payload must contain email.');
        self::assertArrayHasKey('displayName', $arr, 'Public user payload must contain displayName.');
        self::assertArrayHasKey('bio', $arr, 'Public user payload must contain bio.');
        self::assertArrayHasKey('createdAt', $arr, 'Public user payload must contain createdAt.');
        self::assertArrayHasKey('updatedAt', $arr, 'Public user payload must contain updatedAt.');
        self::assertSame('testuser', $arr['username'], 'Username should be mapped correctly in public payload.');
        self::assertSame('Test User', $arr['displayName'], 'Display name should be mapped correctly in public payload.');
    }

    public function testJwtProviderCreatesAndVerifiesAccessToken(): void
    {
        $jwt    = $this->makeJwtProvider();
        $userId = '9876543210000001';
        $token  = $jwt->createAccessToken($userId);

        self::assertNotEmpty($token, 'JWT token should be generated for valid user id.');
        self::assertStringContainsString('.', $token, 'JWT should be a dot-delimited string');

        $claims = $jwt->verifyAccessToken($token);
        self::assertSame($userId, $claims['sub'], 'JWT subject must match the authenticated user id.');
        self::assertGreaterThan(0, $claims['iat'], 'JWT issued-at claim must be a valid unix timestamp.');
        self::assertGreaterThan($claims['iat'], $claims['exp'], 'JWT expiration must be after issued-at.');
        self::assertSame($claims['iat'] + 900, $claims['exp'], 'JWT TTL must match configured provider TTL.');
    }

    public function testJwtProviderThrowsOnTamperedToken(): void
    {
        $this->expectException(\Throwable::class);

        $jwt = $this->makeJwtProvider();
        $jwt->verifyAccessToken('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.tampered.payload');
    }

    public function testRegisterUserHandlerCreatesUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('existsByUsernameOrEmail')->willReturn(false);
        $repo->expects(self::once())->method('save');

        $handler = new RegisterUserHandler($repo, new SnowflakeGenerator(1));
        $user    = $handler->handle(new RegisterUserCommand('newuser', 'New@Example.com', 'secret123'));

        self::assertSame('newuser', $user->username(), 'Registered user should keep the submitted username.');
        self::assertSame('new@example.com', $user->email(), 'Email should be lower-cased');
        self::assertNotEmpty($user->id(), 'Registered user must have a generated id.');
        self::assertNotEmpty($user->passwordHash(), 'Registered user must have a persisted password hash.');
        self::assertFalse($user->verifyPassword('secret123') === false, 'Hash should verify correctly');
    }

    public function testRegisterUserHandlerRejectsExistingIdentity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already exists/i');

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('existsByUsernameOrEmail')->willReturn(true);

        $handler = new RegisterUserHandler($repo, new SnowflakeGenerator(1));
        $handler->handle(new RegisterUserCommand('dup', 'dup@example.com', 'pass'));
    }

    public function testLoginUserHandlerReturnsAccessToken(): void
    {
        $jwt  = $this->makeJwtProvider();
        $user = $this->makeTestUser();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsernameOrEmail')->willReturn($user);

        $handler = new LoginUserHandler($repo, $jwt);
        $result  = $handler->handle(new LoginUserCommand('testuser', 'correct-password'));

        self::assertArrayHasKey('accessToken', $result, 'Login response should include accessToken.');
        self::assertNotEmpty($result['accessToken'], 'Access token should not be empty for valid login.');

        $claims = $jwt->verifyAccessToken($result['accessToken']);
        self::assertSame($user->id(), $claims['sub'], 'Token subject should match logged-in user id.');
    }

    public function testLoginUserHandlerRejectsWrongPassword(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid credentials/i');

        $user = $this->makeTestUser();
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsernameOrEmail')->willReturn($user);

        $handler = new LoginUserHandler($repo, $this->makeJwtProvider());
        $handler->handle(new LoginUserCommand('testuser', 'wrong-password'));
    }

    public function testLoginUserHandlerRejectsUnknownUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid credentials/i');

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsernameOrEmail')->willReturn(null);

        $handler = new LoginUserHandler($repo, $this->makeJwtProvider());
        $handler->handle(new LoginUserCommand('nobody', 'pass'));
    }

    // =========================================================================
    // GROUP 2 — GraphQL (Outbound Tests)
    // Hits the running /graphql endpoint over HTTP.
    // Auto-skipped when backend is unreachable.
    // =========================================================================

    private function graphqlUrl(): string
    {
        $env = getenv('GRAPHQL_TEST_URL');

        return is_string($env) && $env !== '' ? $env : 'http://localhost:8080/graphql';
    }

    /**
     * @param array<string, mixed>|null $variables
     * @return array<string, mixed>
     */
    private function graphqlPost(string $query, ?string $bearerToken = null, ?array $variables = null): array
    {
        $body    = (string) json_encode(['query' => $query, 'variables' => $variables], JSON_THROW_ON_ERROR);
        $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";

        if ($bearerToken !== null) {
            $headers .= "Authorization: Bearer {$bearerToken}\r\n";
        }

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $headers,
                'content'       => $body,
                'ignore_errors' => true,
                'timeout'       => 5,
            ],
        ]);

        $raw = @file_get_contents($this->graphqlUrl(), false, $ctx);

        if ($raw === false) {
            $this->markTestSkipped('GraphQL endpoint unreachable: ' . $this->graphqlUrl());
        }

        return (array) json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    private function skipIfEndpointUnreachable(): void
    {
        $parts = parse_url($this->graphqlUrl());
        $host  = (string) ($parts['host'] ?? 'localhost');
        $port  = (int) ($parts['port'] ?? 80);

        $conn = @fsockopen($host, $port, $errno, $errstr, 2);

        if ($conn === false) {
            $this->markTestSkipped(
                "Backend not reachable at {$host}:{$port}. " .
                'Start containers and apply seed, or set GRAPHQL_TEST_URL.'
            );
        }

        fclose($conn);
    }

    /** Assert no GraphQL error payload and fail with details if present. */
    private function assertNoGraphqlErrors(array $result): void
    {
        if (!empty($result['errors'])) {
            $messages = array_column($result['errors'], 'message');
            self::fail('Unexpected GraphQL errors: ' . implode('; ', $messages));
        }
    }

    /**
     * Outbound 1: register mutation creates a new user and returns profile fields.
     *
     * @return array{username:string,token:string}
     */
    public function testGraphQLRegisterMutation(): void
    {
        $this->skipIfEndpointUnreachable();

        $ts       = (string) time();
        $username = 'gqltestuser' . $ts;
        $email    = $username . '@narsis.test';

        $result = $this->graphqlPost(
            'mutation Register($u:String!,$e:String!,$p:String!) {
                register(username:$u, email:$e, password:$p) {
                    id username email displayName createdAt updatedAt
                }
            }',
            null,
            ['u' => $username, 'e' => $email, 'p' => 'gqltest123']
        );

        $this->assertNoGraphqlErrors($result);
        self::assertSame($username, $result['data']['register']['username'], 'GraphQL register should return the created username.');
        self::assertSame($email, $result['data']['register']['email'], 'GraphQL register should return the created email.');
        self::assertNotEmpty($result['data']['register']['id'], 'GraphQL register should return a generated user id.');
        self::assertNotEmpty($result['data']['register']['createdAt'], 'GraphQL register should return createdAt timestamp.');
        self::assertNotEmpty($result['data']['register']['updatedAt'], 'GraphQL register should return updatedAt timestamp.');
    }

    /**
     * Outbound 2: login mutation returns a valid JWT access token using the seed account.
     */
    public function testGraphQLLoginMutation(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->graphqlPost(
            'mutation Login($u:String!,$p:String!) { login(usernameOrEmail:$u, password:$p) }',
            null,
            ['u' => 'seeduser', 'p' => 'password']
        );

        $this->assertNoGraphqlErrors($result);
        $token = $result['data']['login'] ?? '';
        self::assertNotEmpty($token, 'login mutation must return a non-empty JWT');

        // Token must be a 3-part JWT
        self::assertSame(3, substr_count($token, '.') + 1, 'Expected dot-delimited JWT');
    }

    /**
     * Outbound 3: me query with a valid Bearer token returns the authenticated user.
     */
    public function testGraphQLMeQueryWithValidToken(): void
    {
        $this->skipIfEndpointUnreachable();

        $loginResult = $this->graphqlPost(
            'mutation { login(usernameOrEmail: "seeduser", password: "password") }'
        );
        $token = $loginResult['data']['login'] ?? '';

        if (empty($token)) {
            $this->markTestSkipped('Could not obtain token — is the seed applied?');
        }

        $result = $this->graphqlPost(
            'query { me { id username email displayName bio createdAt updatedAt } }',
            $token
        );

        $this->assertNoGraphqlErrors($result);
        $me = $result['data']['me'];
        self::assertSame('seeduser', $me['username'], 'GraphQL me should resolve the authenticated username.');
        self::assertNotEmpty($me['id'], 'GraphQL me should return authenticated user id.');
        self::assertNotEmpty($me['createdAt'], 'GraphQL me should return createdAt timestamp.');
        self::assertNotEmpty($me['updatedAt'], 'GraphQL me should return updatedAt timestamp.');
    }

    /**
     * Outbound 4: me query without Authorization header must return GraphQL errors.
     */
    public function testGraphQLMeQueryWithoutTokenReturnsErrors(): void
    {
        $this->skipIfEndpointUnreachable();

        $result = $this->graphqlPost('query { me { id username email } }');

        self::assertNotEmpty(
            $result['errors'] ?? [],
            'Expected GraphQL error array when no auth token is provided'
        );
        self::assertNull($result['data']['me'] ?? null, 'me field must be null without auth');
    }
}
