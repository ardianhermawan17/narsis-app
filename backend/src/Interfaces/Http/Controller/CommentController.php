<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\Command\AddComment\AddCommentCommand;
use App\Application\Command\AddComment\AddCommentHandler;
use App\Application\Exception\ValidationException;
use App\Application\Query\ListComments\ListCommentsQueryHandler;
use App\Domain\User\User;

final class CommentController
{
    public function __construct(
        private readonly AddCommentHandler $addCommentHandler,
        private readonly ListCommentsQueryHandler $listCommentsQueryHandler
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:int,body:array<string,mixed>}
     */
    public function add(User $user, array $payload): array
    {
        $postId = trim((string) ($payload['postId'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));

        if ($postId === '') {
            throw new ValidationException('postId is required.');
        }

        if ($content === '') {
            throw new ValidationException('content is required.');
        }

        $comment = $this->addCommentHandler->handle(new AddCommentCommand(
            $postId,
            $user->id(),
            $content,
            isset($payload['parentCommentId']) ? (string) $payload['parentCommentId'] : null
        ));

        return [
            'status' => 201,
            'body' => [
                'message' => 'Comment added.',
                'comment' => $comment,
            ],
        ];
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function list(string $postId, int $limit = 20): array
    {
        return [
            'status' => 200,
            'body' => [
                'comments' => $this->listCommentsQueryHandler->handle($postId, $limit),
            ],
        ];
    }
}