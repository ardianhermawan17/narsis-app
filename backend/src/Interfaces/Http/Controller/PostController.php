<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\Command\CreatePost\CreatePostCommand;
use App\Application\Command\CreatePost\CreatePostHandler;
use App\Application\Command\LikePost\LikePostCommand;
use App\Application\Command\LikePost\LikePostHandler;
use App\Application\Command\UnlikePost\UnlikePostCommand;
use App\Application\Command\UnlikePost\UnlikePostHandler;
use App\Application\Exception\ValidationException;
use App\Application\Query\ListPostCounters\ListPostCountersQueryHandler;
use App\Application\Query\ListUserLikes\ListUserLikesQueryHandler;
use App\Application\Query\ListPosts\ListPostsQueryHandler;
use App\Application\Query\ListUserFeed\ListUserFeedQueryHandler;
use App\Application\Query\ListUserPosts\ListUserPostsQueryHandler;
use App\Domain\User\User;

final class PostController
{
    public function __construct(
        private readonly CreatePostHandler $createPostHandler,
        private readonly ListPostsQueryHandler $listPostsQueryHandler,
        private readonly ListPostCountersQueryHandler $listPostCountersQueryHandler,
        private readonly ListUserPostsQueryHandler $listUserPostsQueryHandler,
        private readonly ListUserLikesQueryHandler $listUserLikesQueryHandler,
        private readonly LikePostHandler $likePostHandler,
        private readonly UnlikePostHandler $unlikePostHandler,
        private readonly ListUserFeedQueryHandler $listUserFeedQueryHandler
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function create(User $user, array $payload): array
    {
        $images = $payload['images'] ?? null;
        if (!is_array($images) || $images === []) {
            throw new ValidationException('images is required and must contain at least one image object.');
        }

        $normalizedImages = [];
        foreach ($images as $image) {
            if (!is_array($image)) {
                throw new ValidationException('Each image payload must be an object.');
            }

            $normalizedImages[] = [
                'imageBase64' => (string) ($image['imageBase64'] ?? ''),
                'mimeType' => isset($image['mimeType']) ? (string) $image['mimeType'] : null,
                'altText' => isset($image['altText']) ? (string) $image['altText'] : null,
                'isPrimary' => isset($image['isPrimary']) ? (bool) $image['isPrimary'] : null,
            ];
        }

        $createdPost = $this->createPostHandler->handle(new CreatePostCommand(
            $user->id(),
            isset($payload['caption']) ? (string) $payload['caption'] : null,
            isset($payload['visibility']) ? (string) $payload['visibility'] : 'public',
            $normalizedImages
        ));

        return [
            'status' => 201,
            'body' => [
                'message' => 'Post created.',
                'post' => $createdPost,
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function list(int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'posts' => $this->listPostsQueryHandler->handle($limit),
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function listCounters(int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'postCounters' => $this->listPostCountersQueryHandler->handle($limit),
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function listByUser(User $user, int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'posts' => $this->listUserPostsQueryHandler->handle($user->id(), $limit),
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function listLikedByUser(User $user, int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'posts' => $this->listUserLikesQueryHandler->handle($user->id(), $limit),
            ],
        ];
    }

    /**
     * @param array{postId?:string} $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function like(User $user, array $payload): array
    {
        $postId = trim((string) ($payload['postId'] ?? ''));
        if ($postId === '') {
            throw new ValidationException('postId is required.');
        }

        $post = $this->likePostHandler->handle(new LikePostCommand($postId, $user->id()));

        return [
            'status' => 200,
            'body' => [
                'message' => 'Post liked.',
                'post' => $post,
            ],
        ];
    }

    /**
     * @param array{postId?:string} $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function unlike(User $user, array $payload): array
    {
        $postId = trim((string) ($payload['postId'] ?? ''));
        if ($postId === '') {
            throw new ValidationException('postId is required.');
        }

        $post = $this->unlikePostHandler->handle(new UnlikePostCommand($postId, $user->id()));

        return [
            'status' => 200,
            'body' => [
                'message' => 'Post unliked.',
                'post' => $post,
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function feed(User $user, int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'posts' => $this->listUserFeedQueryHandler->handle($user->id(), $limit),
            ],
        ];
    }
}