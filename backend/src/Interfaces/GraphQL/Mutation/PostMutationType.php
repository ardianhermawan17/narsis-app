<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Mutation;

use App\Interfaces\GraphQL\Resolver\CreatePostResolver;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostMutationType
{
    public static function create(ObjectType $postType, CreatePostResolver $createPostResolver): ObjectType
    {
        $postImageInput = new InputObjectType([
            'name' => 'PostImageInput',
            'fields' => [
                'imageBase64' => Type::nonNull(Type::string()),
                'mimeType' => Type::string(),
                'altText' => Type::string(),
                'isPrimary' => Type::boolean(),
            ],
        ]);

        return new ObjectType([
            'name' => 'PostMutation',
            'fields' => [
                'createPost' => [
                    'type' => Type::nonNull($postType),
                    'args' => [
                        'caption' => Type::string(),
                        'visibility' => Type::string(),
                        'images' => Type::nonNull(Type::listOf(Type::nonNull($postImageInput))),
                    ],
                    'resolve' => $createPostResolver,
                ],
            ],
        ]);
    }
}