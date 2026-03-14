<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Mutation;

use App\Interfaces\GraphQL\Resolver\like\LikePostResolver;
use App\Interfaces\GraphQL\Resolver\like\UnlikePostResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class LikeMutationType
{
    public static function create(
        ObjectType $postType,
        LikePostResolver $likePostResolver,
        UnlikePostResolver $unlikePostResolver
    ): ObjectType
    {
        return new ObjectType([
            'name' => 'LikeMutation',
            'fields' => [
                'likePost' => [
                    'type' => Type::nonNull($postType),
                    'args' => [
                        'postId' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $likePostResolver,
                ],
                'unlikePost' => [
                    'type' => Type::nonNull($postType),
                    'args' => [
                        'postId' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => $unlikePostResolver,
                ],
            ],
        ]);
    }
}