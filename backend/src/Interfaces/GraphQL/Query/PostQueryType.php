<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\PostsResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostQueryType
{
    public static function create(ObjectType $postType, PostsResolver $postsResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'PostQuery',
            'fields' => [
                'posts' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $postsResolver,
                ],
            ],
        ]);
    }
}