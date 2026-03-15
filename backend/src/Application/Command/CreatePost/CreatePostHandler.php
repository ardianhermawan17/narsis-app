<?php

declare(strict_types=1);

namespace App\Application\Command\CreatePost;

use App\Application\Exception\DuplicateImageDetectedException;
use App\Application\Exception\ValidationException;
use App\Domain\Feed\Repository\UserFeedRepositoryInterface;
use App\Domain\Image\Image;
use App\Domain\Image\ImageFingerprint;
use App\Domain\Image\Repository\ImageRepositoryInterface;
use App\Domain\Post\Post;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Infrastructure\ID\SnowflakeGenerator;
use App\Infrastructure\Image\Copyright\ImageProcessingWorker;
use App\Infrastructure\Image\Storage\LocalImageStorage;

final class CreatePostHandler
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly ImageRepositoryInterface $images,
        private readonly UserFeedRepositoryInterface $feed,
        private readonly SnowflakeGenerator $idGenerator,
        private readonly LocalImageStorage $imageStorage,
        private readonly ImageProcessingWorker $processingWorker,
        private readonly \PDO $pdo
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(CreatePostCommand $command): array
    {
        if ($command->images === []) {
            throw new ValidationException('At least one image is required for creating a post.');
        }

        $visibility = strtolower(trim($command->visibility));
        if (!in_array($visibility, ['public', 'private'], true)) {
            throw new ValidationException('visibility must be public or private.');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $post = new Post(
            $this->idGenerator->nextId(),
            $command->userId,
            $command->caption !== null ? trim($command->caption) : null,
            $visibility,
            $now,
            $now
        );

        $existingFingerprints = $this->images->listFingerprintsByAlgorithm('pdq');

        $this->pdo->beginTransaction();

        try {
            $this->posts->save($post);
            $persistedImages = [];

            foreach ($command->images as $index => $incomingImage) {
                $imageId = $this->idGenerator->nextId();
                $base64 = trim((string) ($incomingImage['imageBase64'] ?? ''));
                if ($base64 === '') {
                    throw new ValidationException('Each image must provide imageBase64 content.');
                }

                $binary = base64_decode($base64, true);
                if ($binary === false) {
                    throw new ValidationException('Each imageBase64 value must be valid base64.');
                }

                $fingerprintPayload = $this->processingWorker->generateFingerprint($binary);
                foreach ($existingFingerprints as $existingFingerprint) {
                    if ($this->processingWorker->isDuplicate(
                        $fingerprintPayload['hashValue'],
                        $existingFingerprint['hashValue'],
                        $existingFingerprint['distanceThreshold']
                    )) {
                        throw new DuplicateImageDetectedException();
                    }
                }

                $storedImage = $this->imageStorage->storeBase64(
                    $base64,
                    $imageId,
                    isset($incomingImage['mimeType']) ? (string) $incomingImage['mimeType'] : null
                );

                $image = new Image(
                    $imageId,
                    $post->id(),
                    'post',
                    $storedImage['storageKey'],
                    $storedImage['mimeType'],
                    $storedImage['width'],
                    $storedImage['height'],
                    $storedImage['sizeBytes'],
                    isset($incomingImage['altText']) ? (string) $incomingImage['altText'] : null,
                    isset($incomingImage['isPrimary']) ? (bool) $incomingImage['isPrimary'] : $index === 0,
                    $now
                );

                $fingerprint = new ImageFingerprint(
                    $this->idGenerator->nextId(),
                    $imageId,
                    $fingerprintPayload['algorithm'],
                    $fingerprintPayload['hashValue'],
                    $fingerprintPayload['hashBytes'],
                    $fingerprintPayload['distanceThreshold'],
                    $now
                );

                $this->images->save($image);
                $this->images->saveFingerprint($fingerprint);
                $persistedImages[] = $image->toArray();
            }

            $this->feed->addPostForAuthorAndFollowers(
                $post->userId(),
                $post->id(),
                (string) $now->format('U.u'),
                $now
            );

            $this->pdo->commit();

            return [
                'id' => $post->id(),
                'userId' => $post->userId(),
                'caption' => $post->caption(),
                'visibility' => $post->visibility(),
                'likesCount' => 0,
                'createdAt' => $post->createdAt()->format(DATE_ATOM),
                'updatedAt' => $post->updatedAt()->format(DATE_ATOM),
                'images' => $persistedImages,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}