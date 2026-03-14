<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Query\FeedQueryType;
use App\Interfaces\GraphQL\Resolver\feed\MyFeedResolver;
use App\Interfaces\GraphQL\Type\ImageTypeFactory;
use App\Interfaces\GraphQL\Type\PostTypeFactory;
use App\Interfaces\Http\Controller\PostController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use GraphQL\Type\Schema;

final class FeedSchemaFactory
{
    public static function create(PostController $postController, JwtAuthMiddleware $authMiddleware): Schema
    {
        $errorHandler = new GraphQlErrorHandler();
        $myFeedResolver = new MyFeedResolver($postController, $authMiddleware, $errorHandler);

        $imageType = ImageTypeFactory::create();
        $postType = PostTypeFactory::create($imageType);
        $queryType = FeedQueryType::create($postType, $myFeedResolver);

        return new Schema([
            'query' => $queryType,
        ]);
    }
}