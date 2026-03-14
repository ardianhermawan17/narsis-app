<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Mutation\LikeMutationType;
use App\Interfaces\GraphQL\Query\LikeQueryType;
use App\Interfaces\GraphQL\Resolver\like\LikePostResolver;
use App\Interfaces\GraphQL\Resolver\like\UnlikePostResolver;
use App\Interfaces\GraphQL\Resolver\like\UserLikeResolver;
use App\Interfaces\GraphQL\Resolver\post\PostsResolver;
use App\Interfaces\GraphQL\Type\ImageTypeFactory;
use App\Interfaces\GraphQL\Type\PostTypeFactory;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use GraphQL\Type\Schema;

final class LikeSchemaFactory
{
    public static function create(PostController $postController, JwtAuthMiddleware $authMiddleware): Schema
    {
        $errorHandler = new GraphQlErrorHandler();

        $postsResolver = new PostsResolver($postController, $errorHandler);
        $userLikeResolver = new UserLikeResolver($postController, $authMiddleware, $errorHandler);
        $likePostResolver = new LikePostResolver($postController, $authMiddleware, $errorHandler);
        $unlikePostResolver = new UnlikePostResolver($postController, $authMiddleware, $errorHandler);

        $imageType = ImageTypeFactory::create();
        $postType = PostTypeFactory::create($imageType);
        $queryType = LikeQueryType::create($postType, $postsResolver, $userLikeResolver);
        $mutationType = LikeMutationType::create($postType, $likePostResolver, $unlikePostResolver);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}