<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Comment\Comment;
use App\Domain\Comment\Repository\CommentRepositoryInterface;

final class PgCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Comment $comment): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (id, post_id, user_id, parent_comment_id, content, created_at, updated_at, is_deleted) '
            . 'VALUES (:id, :post_id, :user_id, :parent_comment_id, :content, :created_at, :updated_at, :is_deleted)'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare comment insert statement.');
        }

        $stmt->execute([
            ':id' => $comment->id(),
            ':post_id' => $comment->postId(),
            ':user_id' => $comment->userId(),
            ':parent_comment_id' => $comment->parentCommentId(),
            ':content' => $comment->content(),
            ':created_at' => $comment->createdAt()->format('Y-m-d H:i:sP'),
            ':updated_at' => $comment->updatedAt()->format('Y-m-d H:i:sP'),
            ':is_deleted' => $comment->isDeleted() ? 'true' : 'false',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByPostId(string $postId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, post_id, user_id, parent_comment_id, content, created_at, updated_at '
            . 'FROM comments '
            . 'WHERE post_id = :post_id AND is_deleted = FALSE '
            . 'ORDER BY created_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare comment list query.');
        }

        $stmt->bindValue(':post_id', $postId, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (string) $row['id'],
                'postId' => (string) $row['post_id'],
                'userId' => (string) $row['user_id'],
                'parentCommentId' => isset($row['parent_comment_id']) ? (string) $row['parent_comment_id'] : null,
                'content' => (string) $row['content'],
                'createdAt' => (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
                'updatedAt' => (new \DateTimeImmutable((string) $row['updated_at']))->format(DATE_ATOM),
            ];
        }, $rows);
    }

    public function incrementPostComments(string $postId, \DateTimeImmutable $updatedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO post_counters (post_id, likes_count, comments_count, shares_count, updated_at) '
            . 'VALUES (:post_id, 0, 1, 0, :updated_at) '
            . 'ON CONFLICT (post_id) DO UPDATE '
            . 'SET comments_count = post_counters.comments_count + 1, updated_at = EXCLUDED.updated_at'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare comment counter upsert statement.');
        }

        $stmt->execute([
            ':post_id' => $postId,
            ':updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
        ]);
    }
}