<?php

declare(strict_types=1);

namespace App\Domain\Post;

final class Post
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly ?string $caption,
        private readonly string $visibility,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
        private readonly bool $isDeleted = false
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function caption(): ?string
    {
        return $this->caption;
    }

    public function visibility(): string
    {
        return $this->visibility;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * @return array{id:string,userId:string,caption:string|null,visibility:string,createdAt:string,updatedAt:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'caption' => $this->caption,
            'visibility' => $this->visibility,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}