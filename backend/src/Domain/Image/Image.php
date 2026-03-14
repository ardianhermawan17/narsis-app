<?php

declare(strict_types=1);

namespace App\Domain\Image;

final class Image
{
    public function __construct(
        private readonly string $id,
        private readonly string $imageableId,
        private readonly string $imageableType,
        private readonly string $storageKey,
        private readonly ?string $mimeType,
        private readonly ?int $width,
        private readonly ?int $height,
        private readonly int $sizeBytes,
        private readonly ?string $altText,
        private readonly bool $isPrimary,
        private readonly \DateTimeImmutable $createdAt
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function imageableId(): string
    {
        return $this->imageableId;
    }

    public function imageableType(): string
    {
        return $this->imageableType;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function width(): ?int
    {
        return $this->width;
    }

    public function height(): ?int
    {
        return $this->height;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function altText(): ?string
    {
        return $this->altText;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array{id:string,storageKey:string,mimeType:string|null,width:int|null,height:int|null,sizeBytes:int,altText:string|null,isPrimary:bool,createdAt:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'storageKey' => $this->storageKey,
            'mimeType' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'sizeBytes' => $this->sizeBytes,
            'altText' => $this->altText,
            'isPrimary' => $this->isPrimary,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}