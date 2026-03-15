<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostCounterTypeFactory
{
    public static function create(): ObjectType
    {
        return new ObjectType([
            'name' => 'PostCounter',
            'fields' => [
                'postId' => Type::nonNull(Type::string()),
                'likesCount' => Type::nonNull(Type::int()),
                'commentsCount' => Type::nonNull(Type::int()),
                'sharesCount' => Type::nonNull(Type::int()),
            ],
        ]);
    }
}