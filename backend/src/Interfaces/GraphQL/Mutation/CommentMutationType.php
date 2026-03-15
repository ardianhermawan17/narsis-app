<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Mutation;

use App\Interfaces\GraphQL\Resolver\comment\AddCommentResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CommentMutationType
{
    public static function create(ObjectType $commentType, AddCommentResolver $addCommentResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'CommentMutation',
            'fields' => [
                'addComment' => [
                    'type' => Type::nonNull($commentType),
                    'args' => [
                        'postId' => Type::nonNull(Type::string()),
                        'content' => Type::nonNull(Type::string()),
                        'parentCommentId' => Type::string(),
                    ],
                    'resolve' => $addCommentResolver,
                ],
            ],
        ]);
    }
}