<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Image\Image;
use App\Domain\Image\ImageFingerprint;
use App\Domain\Image\Repository\ImageRepositoryInterface;

final class PgImageRepository implements ImageRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Image $image): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO images (id, imageable_id, imageable_type, storage_key, mime_type, width, height, size_bytes, alt_text, is_primary, created_at) '
            . 'VALUES (:id, :imageable_id, :imageable_type, :storage_key, :mime_type, :width, :height, :size_bytes, :alt_text, :is_primary, :created_at)'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare image insert statement.');
        }

        $stmt->execute([
            ':id' => $image->id(),
            ':imageable_id' => $image->imageableId(),
            ':imageable_type' => $image->imageableType(),
            ':storage_key' => $image->storageKey(),
            ':mime_type' => $image->mimeType(),
            ':width' => $image->width(),
            ':height' => $image->height(),
            ':size_bytes' => $image->sizeBytes(),
            ':alt_text' => $image->altText(),
            ':is_primary' => $image->isPrimary() ? 'true' : 'false',
            ':created_at' => $image->createdAt()->format('Y-m-d H:i:sP'),
        ]);
    }

    public function saveFingerprint(ImageFingerprint $fingerprint): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO image_fingerprints (id, image_id, algorithm, hash_value, hash_bytes, metadata, created_at) '
            . "VALUES (:id, :image_id, :algorithm, decode(:hash_value_hex, 'hex'), :hash_bytes, :metadata, :created_at)"
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare fingerprint insert statement.');
        }

        $metadata = [
            'distance_threshold' => $fingerprint->distanceThreshold(),
            'source' => 'image_processing_worker',
        ];

        $stmt->bindValue(':id', $fingerprint->id(), \PDO::PARAM_STR);
        $stmt->bindValue(':image_id', $fingerprint->imageId(), \PDO::PARAM_STR);
        $stmt->bindValue(':algorithm', $fingerprint->algorithm(), \PDO::PARAM_STR);
        $stmt->bindValue(':hash_value_hex', bin2hex($fingerprint->hashValue()), \PDO::PARAM_STR);
        $stmt->bindValue(':hash_bytes', $fingerprint->hashBytes(), \PDO::PARAM_INT);
        $stmt->bindValue(':metadata', json_encode($metadata, JSON_THROW_ON_ERROR), \PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $fingerprint->createdAt()->format('Y-m-d H:i:sP'), \PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @return array<int, array{id:string,imageId:string,hashValue:string,distanceThreshold:int}>
     */
    public function listFingerprintsByAlgorithm(string $algorithm): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, image_id, encode(hash_value, 'hex') AS hash_value_hex, metadata "
            . 'FROM image_fingerprints '
            . 'WHERE algorithm = :algorithm'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare fingerprint lookup query.');
        }

        $stmt->execute([':algorithm' => $algorithm]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $metadata = [];
            if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
                $decoded = json_decode($row['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            $result[] = [
                'id' => (string) $row['id'],
                'imageId' => (string) $row['image_id'],
                'hashValue' => hex2bin((string) $row['hash_value_hex']) ?: '',
                'distanceThreshold' => isset($metadata['distance_threshold']) ? (int) $metadata['distance_threshold'] : 5,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, Image>
     */
    public function findByImageable(string $imageableType, string $imageableId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, imageable_id, imageable_type, storage_key, mime_type, width, height, size_bytes, alt_text, is_primary, created_at '
            . 'FROM images '
            . 'WHERE imageable_type = :imageable_type AND imageable_id = :imageable_id '
            . 'ORDER BY created_at ASC'
        );

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare image query.');
        }

        $stmt->execute([
            ':imageable_type' => $imageableType,
            ':imageable_id' => $imageableId,
        ]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $images = [];
        foreach ($rows as $row) {
            $images[] = new Image(
                (string) $row['id'],
                (string) $row['imageable_id'],
                (string) $row['imageable_type'],
                (string) $row['storage_key'],
                isset($row['mime_type']) ? (string) $row['mime_type'] : null,
                isset($row['width']) ? (int) $row['width'] : null,
                isset($row['height']) ? (int) $row['height'] : null,
                isset($row['size_bytes']) ? (int) $row['size_bytes'] : 0,
                isset($row['alt_text']) ? (string) $row['alt_text'] : null,
                isset($row['is_primary']) ? (bool) $row['is_primary'] : false,
                new \DateTimeImmutable((string) $row['created_at'])
            );
        }

        return $images;
    }
}