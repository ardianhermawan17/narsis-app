<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\post\PostsResolver;
use App\Interfaces\GraphQL\Resolver\post\PostCountersResolver;
use App\Interfaces\GraphQL\Resolver\post\UserPostResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostQueryType
{
    public static function create(
        ObjectType $postType,
        ObjectType $postCounterType,
        PostsResolver $postsResolver,
        PostCountersResolver $postCountersResolver,
        UserPostResolver $userPostResolver
    ): ObjectType
    {
        return new ObjectType([
            'name' => 'PostQuery',
            'fields' => [
                'allPost' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $postsResolver,
                ],
                'userPost' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $userPostResolver,
                ],
                'postCounters' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postCounterType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $postCountersResolver,
                ],
            ],
        ]);
    }
}