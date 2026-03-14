<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Mutation\AuthMutationType;
use App\Interfaces\GraphQL\Query\AuthQueryType;
use App\Interfaces\GraphQL\Resolver\auth\LoginResolver;
use App\Interfaces\GraphQL\Resolver\auth\MeResolver;
use App\Interfaces\GraphQL\Resolver\auth\RefreshTokenResolver;
use App\Interfaces\GraphQL\Resolver\auth\RegisterResolver;
use App\Interfaces\GraphQL\Type\AuthTokenTypeFactory;
use App\Interfaces\GraphQL\Type\UserTypeFactory;
use App\Interfaces\Http\Controller\AuthController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use GraphQL\Type\Schema;

final class AuthSchemaFactory
{
    public static function create(AuthController $authController, JwtAuthMiddleware $authMiddleware): Schema
    {
        $errorHandler = new GraphQlErrorHandler();
        $meResolver = new MeResolver($authController, $authMiddleware, $errorHandler);
        $registerResolver = new RegisterResolver($authController, $errorHandler);
        $loginResolver = new LoginResolver($authController, $errorHandler);
        $refreshTokenResolver = new RefreshTokenResolver($authController, $errorHandler);

        $userType = UserTypeFactory::create();
        $authTokenType = AuthTokenTypeFactory::create();
        $queryType = AuthQueryType::create($userType, $meResolver);
        $mutationType = AuthMutationType::create(
            $userType,
            $authTokenType,
            $registerResolver,
            $loginResolver,
            $refreshTokenResolver
        );

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}