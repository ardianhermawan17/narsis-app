<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class AuthTokenTypeFactory
{
    public static function create(): ObjectType
    {
        return new ObjectType([
            'name' => 'AuthTokenPair',
            'fields' => [
                'accessToken' => Type::nonNull(Type::string()),
                'refreshToken' => Type::nonNull(Type::string()),
                'tokenType' => Type::nonNull(Type::string()),
                'expiresIn' => Type::nonNull(Type::int()),
            ],
        ]);
    }
}