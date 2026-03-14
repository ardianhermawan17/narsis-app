<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Mutation;

use App\Interfaces\GraphQL\Resolver\LoginResolver;
use App\Interfaces\GraphQL\Resolver\RegisterResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class AuthMutationType
{
    public static function create(
        ObjectType $userType,
        RegisterResolver $registerResolver,
        LoginResolver $loginResolver
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
                    'type' => Type::nonNull(Type::string()),
                    'args' => [
                        'usernameOrEmail' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $loginResolver,
                ],
            ],
        ]);
    }
}
