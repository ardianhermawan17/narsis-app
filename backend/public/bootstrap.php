<?php

declare(strict_types=1);

use App\Application\Command\CreatePost\CreatePostHandler;
use App\Application\Command\LoginUser\LoginUserHandler;
use App\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Application\GraphQL\Resource\TopLevelResourceExtractor;
use App\Application\GraphQL\Validation\GraphQlDocumentLimiter;
use App\Application\Query\ListPosts\ListPostsQueryHandler;
use App\Infrastructure\Auth\JwtProvider;
use App\Infrastructure\GraphQL\Cache\LruAstCache;
use App\Infrastructure\GraphQL\Resource\CanonicalResourceQueryFactory;
use App\Infrastructure\GraphQL\Resource\PersistedQueryResourceMapper;
use App\Infrastructure\ID\SnowflakeGenerator;
use App\Infrastructure\Image\Copyright\ImageProcessingWorker;
use App\Infrastructure\Image\Storage\LocalImageStorage;
use App\Infrastructure\Persistence\PgImageRepository;
use App\Infrastructure\Persistence\PgPostRepository;
use App\Infrastructure\Persistence\PgSessionRepository;
use App\Infrastructure\Persistence\PgUserRepository;
use App\Interfaces\GraphQL\Schema\AuthSchemaFactory;
use App\Interfaces\GraphQL\Schema\PostSchemaFactory;
use App\Interfaces\GraphQL\Schema\SchemaRegistry;
use App\Interfaces\GraphQL\Error\GraphQlTransportErrorHandler;
use App\Interfaces\Http\Adapter\GraphQlResourceGatewayAdapter;
use App\Interfaces\Http\Controller\AuthController;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Error\HttpErrorHandler;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    Dotenv::createImmutable($envPath)->safeLoad();
}

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
$jwtRefreshTtl = (int) ($_ENV['JWT_REFRESH_TTL'] ?? '1209600');

$userRepository = new PgUserRepository($pdo);
$postRepository = new PgPostRepository($pdo);
$imageRepository = new PgImageRepository($pdo);
$sessionRepository = new PgSessionRepository($pdo);
$idGenerator = new SnowflakeGenerator($serverId);
$jwtProvider = new JwtProvider($jwtSecret, $jwtTtl, $jwtRefreshTtl);

$imageStorageRoot = dirname(__DIR__) . '/storage/images';
$localImageStorage = new LocalImageStorage($imageStorageRoot);
$imageProcessingWorker = new ImageProcessingWorker();

$createPostHandler = new CreatePostHandler(
    $postRepository,
    $imageRepository,
    $idGenerator,
    $localImageStorage,
    $imageProcessingWorker,
    $pdo
);
$listPostsQueryHandler = new ListPostsQueryHandler($postRepository);

$registerHandler = new RegisterUserHandler($userRepository, $idGenerator);
$loginHandler = new LoginUserHandler($userRepository, $jwtProvider, $sessionRepository, $idGenerator);
$refreshTokenHandler = new RefreshTokenHandler($sessionRepository, $userRepository, $jwtProvider);

$authController = new AuthController($registerHandler, $loginHandler, $refreshTokenHandler);
$postController = new PostController($createPostHandler, $listPostsQueryHandler);
$authMiddleware = new JwtAuthMiddleware($jwtProvider, $userRepository);
$httpErrorHandler = new HttpErrorHandler();
$graphQlTransportErrorHandler = new GraphQlTransportErrorHandler();

$schemaRegistry = new SchemaRegistry([
    'auth' => static fn () => AuthSchemaFactory::create($authController, $authMiddleware),
    'posts' => static fn () => PostSchemaFactory::create($postController, $authMiddleware),
], 'auth', [
    'me' => 'auth',
    'register' => 'auth',
    'login' => 'auth',
    'refreshtoken' => 'auth',
    'posts' => 'posts',
    'createpost' => 'posts',
]);

$resourceMapper = new PersistedQueryResourceMapper(new CanonicalResourceQueryFactory());
$graphQlGatewayAdapter = new GraphQlResourceGatewayAdapter(
    $resourceMapper,
    new TopLevelResourceExtractor(),
    new GraphQlDocumentLimiter(maxDepth: 8, maxCost: 300),
    new LruAstCache(capacity: 128)
);

return [
    'authController' => $authController,
    'postController' => $postController,
    'authMiddleware' => $authMiddleware,
    'httpErrorHandler' => $httpErrorHandler,
    'graphQlTransportErrorHandler' => $graphQlTransportErrorHandler,
    'graphQlGatewayAdapter' => $graphQlGatewayAdapter,
    'graphQlSchemaRegistry' => $schemaRegistry,
];
