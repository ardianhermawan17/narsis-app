<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostTypeFactory
{
    public static function create(ObjectType $imageType): ObjectType
    {
        return new ObjectType([
            'name' => 'Post',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'userId' => Type::nonNull(Type::string()),
                'caption' => Type::string(),
                'visibility' => Type::nonNull(Type::string()),
                'likesCount' => Type::nonNull(Type::int()),
                'createdAt' => Type::nonNull(Type::string()),
                'updatedAt' => Type::nonNull(Type::string()),
                'images' => Type::nonNull(Type::listOf(Type::nonNull($imageType))),
            ],
        ]);
    }
}