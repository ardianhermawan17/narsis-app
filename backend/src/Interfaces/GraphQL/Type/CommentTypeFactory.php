<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CommentTypeFactory
{
    public static function create(): ObjectType
    {
        return new ObjectType([
            'name' => 'Comment',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'postId' => Type::nonNull(Type::string()),
                'userId' => Type::nonNull(Type::string()),
                'parentCommentId' => Type::string(),
                'content' => Type::nonNull(Type::string()),
                'createdAt' => Type::nonNull(Type::string()),
                'updatedAt' => Type::nonNull(Type::string()),
            ],
        ]);
    }
}