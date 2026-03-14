<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class UserTypeFactory
{
    public static function create(): ObjectType
    {
        return new ObjectType([
            'name' => 'User',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'username' => Type::nonNull(Type::string()),
                'email' => Type::nonNull(Type::string()),
                'displayName' => Type::string(),
                'bio' => Type::string(),
                'createdAt' => Type::nonNull(Type::string()),
                'updatedAt' => Type::nonNull(Type::string()),
            ],
        ]);
    }
}
