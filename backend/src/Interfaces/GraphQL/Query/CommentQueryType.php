<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\comment\CommentsResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CommentQueryType
{
    public static function create(ObjectType $commentType, CommentsResolver $commentsResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'CommentQuery',
            'fields' => [
                'comment' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($commentType))),
                    'args' => [
                        'postId' => Type::nonNull(Type::string()),
                        'limit' => Type::int(),
                    ],
                    'resolve' => $commentsResolver,
                ],
            ],
        ]);
    }
}