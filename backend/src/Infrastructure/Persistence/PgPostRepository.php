<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Post\Post;
use App\Domain\Post\Repository\PostRepositoryInterface;

final class PgPostRepository implements PostRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Post $post): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (id, user_id, caption, visibility, created_at, updated_at, is_deleted) '
            . 'VALUES (:id, :user_id, :caption, :visibility, :created_at, :updated_at, :is_deleted)'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare post insert statement.');
        }

        $stmt->execute([
            ':id' => $post->id(),
            ':user_id' => $post->userId(),
            ':caption' => $post->caption(),
            ':visibility' => $post->visibility(),
            ':created_at' => $post->createdAt()->format('Y-m-d H:i:sP'),
            ':updated_at' => $post->updatedAt()->format('Y-m-d H:i:sP'),
            ':is_deleted' => $post->isDeleted() ? 'true' : 'false',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestWithImages(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary '
            . 'FROM posts p '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'WHERE p.is_deleted = FALSE '
            . 'ORDER BY p.created_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare posts query.');
        }

        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $postId = (string) $row['id'];
            if (!isset($grouped[$postId])) {
                $grouped[$postId] = [
                    'id' => $postId,
                    'userId' => (string) $row['user_id'],
                    'caption' => isset($row['caption']) ? (string) $row['caption'] : null,
                    'visibility' => (string) $row['visibility'],
                    'createdAt' => (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
                    'updatedAt' => (new \DateTimeImmutable((string) $row['updated_at']))->format(DATE_ATOM),
                    'images' => [],
                ];
            }

            if (isset($row['image_id']) && $row['image_id'] !== null) {
                $grouped[$postId]['images'][] = [
                    'id' => (string) $row['image_id'],
                    'storageKey' => (string) $row['storage_key'],
                    'mimeType' => isset($row['mime_type']) ? (string) $row['mime_type'] : null,
                    'width' => isset($row['width']) ? (int) $row['width'] : null,
                    'height' => isset($row['height']) ? (int) $row['height'] : null,
                    'sizeBytes' => isset($row['size_bytes']) ? (int) $row['size_bytes'] : 0,
                    'altText' => isset($row['alt_text']) ? (string) $row['alt_text'] : null,
                    'isPrimary' => isset($row['is_primary']) ? (bool) $row['is_primary'] : false,
                ];
            }
        }

        return array_values($grouped);
    }
}