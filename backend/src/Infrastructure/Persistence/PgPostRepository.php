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
     * @return array<string, mixed>|null
     */
    public function findByIdWithImages(string $postId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, '
            . 'COALESCE(pc.likes_count, 0) AS likes_count, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary, i.created_at AS image_created_at '
            . 'FROM posts p '
            . 'LEFT JOIN post_counters pc ON pc.post_id = p.id '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'WHERE p.id = :post_id AND p.is_deleted = FALSE '
            . 'ORDER BY i.created_at ASC'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare post lookup query.');
        }

        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->bindValue(':post_id', $postId, \PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $first = $rows[0];
        $post = [
            'id' => (string) $first['id'],
            'userId' => (string) $first['user_id'],
            'caption' => isset($first['caption']) ? (string) $first['caption'] : null,
            'visibility' => (string) $first['visibility'],
            'createdAt' => (new \DateTimeImmutable((string) $first['created_at']))->format(DATE_ATOM),
            'updatedAt' => (new \DateTimeImmutable((string) $first['updated_at']))->format(DATE_ATOM),
            'likesCount' => (int) ($first['likes_count'] ?? 0),
            'images' => [],
        ];

        foreach ($rows as $row) {
            if ($row['image_id'] === null) {
                continue;
            }

            $post['images'][] = [
                'id' => (string) $row['image_id'],
                'storageKey' => (string) $row['storage_key'],
                'mimeType' => isset($row['mime_type']) ? (string) $row['mime_type'] : null,
                'width' => isset($row['width']) ? (int) $row['width'] : null,
                'height' => isset($row['height']) ? (int) $row['height'] : null,
                'sizeBytes' => isset($row['size_bytes']) ? (int) $row['size_bytes'] : 0,
                'altText' => isset($row['alt_text']) ? (string) $row['alt_text'] : null,
                'isPrimary' => (bool) $row['is_primary'],
                'createdAt' => isset($row['image_created_at'])
                    ? (new \DateTimeImmutable((string) $row['image_created_at']))->format(DATE_ATOM)
                    : $post['createdAt'],
            ];
        }

        return $post;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestWithImages(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, COALESCE(pc.likes_count, 0) AS likes_count, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary, i.created_at AS image_created_at '
            . 'FROM posts p '
            . 'LEFT JOIN post_counters pc ON pc.post_id = p.id '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'WHERE p.is_deleted = FALSE AND p.visibility = :visibility '
            . 'ORDER BY p.created_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare posts query.');
        }

        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->bindValue(':visibility', 'public', \PDO::PARAM_STR);
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
                    'likesCount' => (int) ($row['likes_count'] ?? 0),
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
                    'createdAt' => isset($row['image_created_at'])
                        ? (new \DateTimeImmutable((string) $row['image_created_at']))->format(DATE_ATOM)
                        : (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserIdWithImages(string $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, COALESCE(pc.likes_count, 0) AS likes_count, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary, i.created_at AS image_created_at '
            . 'FROM posts p '
            . 'LEFT JOIN post_counters pc ON pc.post_id = p.id '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'WHERE p.user_id = :user_id AND p.is_deleted = FALSE '
            . 'ORDER BY p.created_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user posts query.');
        }

        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        return $this->mapGroupedPostRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLikedByUserIdWithImages(string $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, COALESCE(pc.likes_count, 0) AS likes_count, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary, i.created_at AS image_created_at '
            . 'FROM likes l '
            . 'JOIN posts p ON p.id = l.post_id AND p.is_deleted = FALSE '
            . 'LEFT JOIN post_counters pc ON pc.post_id = p.id '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'WHERE l.user_id = :user_id '
            . 'ORDER BY l.created_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user liked posts query.');
        }

        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        return $this->mapGroupedPostRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPostCounters(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT post_id, likes_count, comments_count, shares_count '
            . 'FROM post_counters '
            . 'ORDER BY updated_at DESC '
            . 'LIMIT :limit'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare post counters query.');
        }

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'postId' => (string) $row['post_id'],
                'likesCount' => (int) ($row['likes_count'] ?? 0),
                'commentsCount' => (int) ($row['comments_count'] ?? 0),
                'sharesCount' => (int) ($row['shares_count'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapGroupedPostRows(array $rows): array
    {
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
                    'likesCount' => (int) ($row['likes_count'] ?? 0),
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
                    'createdAt' => isset($row['image_created_at'])
                        ? (new \DateTimeImmutable((string) $row['image_created_at']))->format(DATE_ATOM)
                        : (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
                ];
            }
        }

        return array_values($grouped);
    }
}