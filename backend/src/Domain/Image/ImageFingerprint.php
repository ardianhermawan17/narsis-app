<?php

declare(strict_types=1);

namespace App\Domain\Image;

final class ImageFingerprint
{
    public function __construct(
        private readonly string $id,
        private readonly string $imageId,
        private readonly string $algorithm,
        private readonly string $hashValue,
        private readonly int $hashBytes,
        private readonly int $distanceThreshold,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?array $metadata = null
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function imageId(): string
    {
        return $this->imageId;
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    public function hashValue(): string
    {
        return $this->hashValue;
    }

    public function hashBytes(): int
    {
        return $this->hashBytes;
    }

    public function distanceThreshold(): int
    {
        return $this->distanceThreshold;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }
}