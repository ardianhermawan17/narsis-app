<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Mutation\PostMutationType;
use App\Interfaces\GraphQL\Query\PostQueryType;
use App\Interfaces\GraphQL\Resolver\CreatePostResolver;
use App\Interfaces\GraphQL\Resolver\PostsResolver;
use App\Interfaces\GraphQL\Type\ImageTypeFactory;
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
        $createPostResolver = new CreatePostResolver($postController, $authMiddleware, $errorHandler);

        $imageType = ImageTypeFactory::create();
        $postType = PostTypeFactory::create($imageType);
        $queryType = PostQueryType::create($postType, $postsResolver);
        $mutationType = PostMutationType::create($postType, $createPostResolver);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}