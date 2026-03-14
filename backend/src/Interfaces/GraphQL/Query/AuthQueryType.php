<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Query;

use App\Interfaces\GraphQL\Resolver\MeResolver;
use GraphQL\Type\Definition\ObjectType;

final class AuthQueryType
{
    public static function create(ObjectType $userType, MeResolver $meResolver): ObjectType
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => [
                'me' => [
                    'type' => $userType,
                    'resolve' => $meResolver,
                ],
            ],
        ]);
    }
}
