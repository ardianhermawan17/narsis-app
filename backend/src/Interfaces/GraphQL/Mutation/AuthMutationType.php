<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Mutation;

use App\Interfaces\GraphQL\Resolver\auth\LoginResolver;
use App\Interfaces\GraphQL\Resolver\auth\LogoutResolver;
use App\Interfaces\GraphQL\Resolver\auth\RefreshTokenResolver;
use App\Interfaces\GraphQL\Resolver\auth\RegisterResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class AuthMutationType
{
    public static function create(
        ObjectType $userType,
        ObjectType $authTokenType,
        RegisterResolver $registerResolver,
        LoginResolver $loginResolver,
        RefreshTokenResolver $refreshTokenResolver,
        LogoutResolver $logoutResolver
    ): ObjectType {
        return new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                'register' => [
                    'type' => $userType,
                    'args' => [
                        'username' => Type::nonNull(Type::string()),
                        'email' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $registerResolver,
                ],
                'login' => [
                    'type' => Type::nonNull($authTokenType),
                    'args' => [
                        'usernameOrEmail' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $loginResolver,
                ],
                'refreshToken' => [
                    'type' => Type::nonNull($authTokenType),
                    'args' => [
                        'refreshToken' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $refreshTokenResolver,
                ],
                'logout' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'refreshToken' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $logoutResolver,
                ],
            ],
        ]);
    }
}