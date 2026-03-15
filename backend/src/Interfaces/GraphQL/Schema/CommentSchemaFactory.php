<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use App\Interfaces\GraphQL\Error\GraphQlErrorHandler;
use App\Interfaces\GraphQL\Mutation\CommentMutationType;
use App\Interfaces\GraphQL\Query\CommentQueryType;
use App\Interfaces\GraphQL\Resolver\comment\AddCommentResolver;
use App\Interfaces\GraphQL\Resolver\comment\UserCommentResolver;
use App\Interfaces\GraphQL\Type\CommentTypeFactory;
use App\Interfaces\Http\Controller\CommentController;
use App\Interfaces\Http\Middleware\JwtAuthMiddleware;
use GraphQL\Type\Schema;

final class CommentSchemaFactory
{
    public static function create(CommentController $commentController, JwtAuthMiddleware $authMiddleware): Schema
    {
        $errorHandler = new GraphQlErrorHandler();
        $userCommentResolver = new UserCommentResolver($commentController, $authMiddleware, $errorHandler);
        $addCommentResolver = new AddCommentResolver($commentController, $authMiddleware, $errorHandler);

        $commentType = CommentTypeFactory::create();
        $queryType = CommentQueryType::create($commentType, $userCommentResolver);
        $mutationType = CommentMutationType::create($commentType, $addCommentResolver);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
    }
}