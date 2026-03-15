<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\comment\UserCommentResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CommentQueryType
{
    public static function create(ObjectType $commentType, UserCommentResolver $userCommentResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'CommentQuery',
            'fields' => [
                'userComment' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($commentType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $userCommentResolver,
                ],
            ],
        ]);
    }
}