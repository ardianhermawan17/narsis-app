<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\Command\CreatePost\CreatePostCommand;
use App\Application\Command\CreatePost\CreatePostHandler;
use App\Application\Exception\ValidationException;
use App\Application\Query\ListPosts\ListPostsQueryHandler;
use App\Domain\User\User;

final class PostController
{
    public function __construct(
        private readonly CreatePostHandler $createPostHandler,
        private readonly ListPostsQueryHandler $listPostsQueryHandler
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
}