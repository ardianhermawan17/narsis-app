<?php

declare(strict_types=1);

use App\Application\Command\LoginUser\LoginUserHandler;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Infrastructure\Auth\JwtProvider;
use App\Infrastructure\ID\SnowflakeGenerator;
use App\Infrastructure\Persistence\PgUserRepository;
use App\Interfaces\GraphQL\Schema\AuthSchemaFactory;
use App\Interfaces\Http\Controller\AuthController;
use App\Interfaces\Http\Error\HttpErrorHandler;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use Dotenv\Dotenv;
use GraphQL\GraphQL;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Apache/PHP setups may not always expose Authorization in HTTP_AUTHORIZATION.
 */
function resolveAuthorizationHeader(): ?string
{
    $direct = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (is_string($direct) && $direct !== '') {
        return $direct;
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if (is_string($auth) && $auth !== '') {
                return $auth;
            }
        }
    }

    return null;
}

$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    Dotenv::createImmutable($envPath)->safeLoad();
}

header('Content-Type: application/json');

try {
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'narsisdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? 'narsis';
    $dbPass = $_ENV['DB_PASSWORD'] ?? 'narsis';

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbHost, $dbPort, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $serverId = (int) ($_ENV['SERVER_ID'] ?? '1');
    $jwtSecret = (string) ($_ENV['JWT_SECRET'] ?? '');
    $jwtTtl = (int) ($_ENV['JWT_TTL'] ?? '900');

    $userRepository = new PgUserRepository($pdo);
    $idGenerator = new SnowflakeGenerator($serverId);
    $jwtProvider = new JwtProvider($jwtSecret, $jwtTtl);

    $registerHandler = new RegisterUserHandler($userRepository, $idGenerator);
    $loginHandler = new LoginUserHandler($userRepository, $jwtProvider);
    $authController = new AuthController($registerHandler, $loginHandler);
    $authMiddleware = new JwtAuthMiddleware($jwtProvider, $userRepository);
    $httpErrorHandler = new HttpErrorHandler();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $rawInput = file_get_contents('php://input');
    $payload = $rawInput !== false && $rawInput !== '' ? json_decode($rawInput, true) : [];

    if (!is_array($payload)) {
        $payload = [];
    }

    if ($method === 'GET' && $path === '/health') {
        $response = [
            'status' => 200,
            'body' => [
                'status' => 'ok',
                'message' => 'Service is healthy'
            ]
        ];
        http_response_code($response['status']);
        echo json_encode($response['body']);
        exit;
    }

    if ($method === 'GET' && $path === '/graphql/schema') {
        $schemaPath = dirname(__DIR__) . '/graphql.schema';
        if (!file_exists($schemaPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'Schema file not found.']);
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo (string) file_get_contents($schemaPath);
        exit;
    }

    if ($method === 'POST' && $path === '/api/register') {
        try {
            $response = $authController->register($payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }
        http_response_code($response['status']);
        echo json_encode($response['body']);
        exit;
    }

    if ($method === 'POST' && $path === '/api/login') {
        try {
            $response = $authController->login($payload);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }
        http_response_code($response['status']);
        echo json_encode($response['body']);
        exit;
    }

    if ($method === 'GET' && $path === '/api/profile') {
        $authHeader = resolveAuthorizationHeader();

        try {
            $user = $authMiddleware->authenticate($authHeader);
            $response = $authController->profile($user);
        } catch (Throwable $e) {
            $response = $httpErrorHandler->handle($e);
        }

        http_response_code($response['status']);
        echo json_encode($response['body']);
        exit;
    }

    if ($method === 'POST' && $path === '/graphql') {
        $query = isset($payload['query']) ? (string) $payload['query'] : '';
        $variables = isset($payload['variables']) && is_array($payload['variables']) ? $payload['variables'] : null;

        if ($query === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Missing GraphQL query.']);
            exit;
        }

        $schema = AuthSchemaFactory::create($authController, $authMiddleware);
        $result = GraphQL::executeQuery(
            $schema,
            $query,
            null,
            ['authorizationHeader' => resolveAuthorizationHeader()],
            $variables
        );

        $output = $result->toArray();
        http_response_code(isset($output['errors']) ? 400 : 200);
        echo json_encode($output);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Route not found.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error.']);
}