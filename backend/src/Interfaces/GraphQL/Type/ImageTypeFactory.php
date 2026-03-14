<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class ImageTypeFactory
{
    public static function create(): ObjectType
    {
        return new ObjectType([
            'name' => 'Image',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'storageKey' => Type::nonNull(Type::string()),
                'mimeType' => Type::string(),
                'width' => Type::int(),
                'height' => Type::int(),
                'sizeBytes' => Type::nonNull(Type::int()),
                'altText' => Type::string(),
                'isPrimary' => Type::nonNull(Type::boolean()),
                'createdAt' => Type::nonNull(Type::string()),
            ],
        ]);
    }
}