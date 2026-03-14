<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\feed\MyFeedResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class FeedQueryType
{
    public static function create(ObjectType $postType, MyFeedResolver $myFeedResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'FeedQuery',
            'fields' => [
                'myFeed' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($postType))),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => $myFeedResolver,
                ],
            ],
        ]);
    }
}