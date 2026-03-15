<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Mutation\PostMutationType;
use App\Interfaces\GraphQL\Query\PostQueryType;
use App\Interfaces\GraphQL\Resolver\post\CreatePostResolver;
use App\Interfaces\GraphQL\Resolver\post\PostCountersResolver;
use App\Interfaces\GraphQL\Resolver\post\PostsResolver;
use App\Interfaces\GraphQL\Resolver\post\UserPostResolver;
use App\Interfaces\GraphQL\Type\ImageTypeFactory;
use App\Interfaces\GraphQL\Type\PostCounterTypeFactory;
use App\Interfaces\GraphQL\Type\PostTypeFactory;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use GraphQL\Type\Schema;

final class PostSchemaFactory
{
    public static function create(PostController $postController, JwtAuthMiddleware $authMiddleware): Schema
    {
        $errorHandler = new GraphQlErrorHandler();

        $postsResolver = new PostsResolver($postController, $errorHandler);
        $postCountersResolver = new PostCountersResolver($postController, $errorHandler);
        $userPostResolver = new UserPostResolver($postController, $authMiddleware, $errorHandler);
        $createPostResolver = new CreatePostResolver($postController, $authMiddleware, $errorHandler);

        $imageType = ImageTypeFactory::create();
        $postType = PostTypeFactory::create($imageType);
        $postCounterType = PostCounterTypeFactory::create();
        $queryType = PostQueryType::create($postType, $postCounterType, $postsResolver, $postCountersResolver, $userPostResolver);
        $mutationType = PostMutationType::create($postType, $createPostResolver);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}