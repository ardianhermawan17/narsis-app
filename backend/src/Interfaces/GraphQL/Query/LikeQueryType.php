<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\like\UserLikeResolver;
use App\Interfaces\GraphQL\Resolver\post\PostsResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class LikeQueryType
{
    public static function create(
        ObjectType $postType,
        PostsResolver $postsResolver,
        UserLikeResolver $userLikeResolver
    ): ObjectType
    {
        return new ObjectType([
            'name' => 'LikeQuery',
            'fields' => [
                'allLike' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $postsResolver,
                ],
                'userLike' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $userLikeResolver,
                ],
            ],
        ]);
    }
}