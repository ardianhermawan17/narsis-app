<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Feed\Repository\UserFeedRepositoryInterface;
use App\Domain\Feed\UserFeed;

final class PgUserFeedRepository implements UserFeedRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function addPostForAuthorAndFollowers(string $authorUserId, string $postId, string $score, \DateTimeImmutable $insertedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_feed (user_id, post_id, score, inserted_at) '
            . 'SELECT DISTINCT candidate_user_id, :post_id, :score, :inserted_at '
            . 'FROM ( '
            . 'SELECT :author_user_id AS candidate_user_id '
            . 'UNION '
            . 'SELECT follower_id AS candidate_user_id FROM follows WHERE followee_id = :author_user_id '
            . ') AS feed_candidates '
            . 'ON CONFLICT (user_id, post_id) DO UPDATE '
            . 'SET score = EXCLUDED.score, inserted_at = EXCLUDED.inserted_at'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user_feed fanout statement.');
        }

        $stmt->execute([
            ':author_user_id' => $authorUserId,
            ':post_id' => $postId,
            ':score' => $score,
            ':inserted_at' => $insertedAt->format('Y-m-d H:i:sP'),
        ]);
    }

    public function upsertPostForUser(string $userId, string $postId, string $score, \DateTimeImmutable $insertedAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_feed (user_id, post_id, score, inserted_at) '
            . 'VALUES (:user_id, :post_id, :score, :inserted_at) '
            . 'ON CONFLICT (user_id, post_id) DO UPDATE '
            . 'SET score = EXCLUDED.score, inserted_at = EXCLUDED.inserted_at'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user_feed upsert statement.');
        }

        $stmt->execute([
            ':user_id' => $userId,
            ':post_id' => $postId,
            ':score' => $score,
            ':inserted_at' => $insertedAt->format('Y-m-d H:i:sP'),
        ]);
    }

    /**
     * @return array<int, UserFeed>
     */
    public function findByUserId(string $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'WITH selected_feed AS ( '
            . 'SELECT uf.post_id, uf.score, uf.inserted_at '
            . 'FROM user_feed uf '
            . 'JOIN posts p ON p.id = uf.post_id AND p.is_deleted = FALSE '
            . 'WHERE uf.user_id = :user_id '
            . 'ORDER BY uf.score DESC, uf.inserted_at DESC '
            . 'LIMIT :limit '
            . ') '
            . 'SELECT p.id, p.user_id, p.caption, p.visibility, p.created_at, p.updated_at, '
            . 'COALESCE(pc.likes_count, 0) AS likes_count, sf.score, sf.inserted_at, '
            . 'i.id AS image_id, i.storage_key, i.mime_type, i.width, i.height, i.size_bytes, i.alt_text, i.is_primary, i.created_at AS image_created_at '
            . 'FROM selected_feed sf '
            . 'JOIN posts p ON p.id = sf.post_id '
            . 'LEFT JOIN post_counters pc ON pc.post_id = p.id '
            . 'LEFT JOIN images i ON i.imageable_type = :imageable_type AND i.imageable_id = p.id '
            . 'ORDER BY sf.score DESC, sf.inserted_at DESC, i.created_at ASC'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare user feed query statement.');
        }

        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':imageable_type', 'post', \PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows) || $rows === []) {
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
                    'likesCount' => (int) ($row['likes_count'] ?? 0),
                    'score' => (string) $row['score'],
                    'insertedAt' => (new \DateTimeImmutable((string) $row['inserted_at']))->format(DATE_ATOM),
                    'createdAt' => (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM),
                    'updatedAt' => (new \DateTimeImmutable((string) $row['updated_at']))->format(DATE_ATOM),
                    'images' => [],
                ];
            }

            if ($row['image_id'] === null) {
                continue;
            }

            $grouped[$postId]['images'][] = [
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
                    : (string) $grouped[$postId]['createdAt'],
            ];
        }

        $feed = [];
        foreach ($grouped as $post) {
            $feed[] = new UserFeed(
                (string) $post['id'],
                (string) $post['userId'],
                isset($post['caption']) ? (string) $post['caption'] : null,
                (string) $post['visibility'],
                (int) $post['likesCount'],
                (string) $post['score'],
                (string) $post['insertedAt'],
                (string) $post['createdAt'],
                (string) $post['updatedAt'],
                (array) $post['images']
            );
        }

        return $feed;
    }
}
