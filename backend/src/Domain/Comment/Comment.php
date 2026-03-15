<?php

declare(strict_types=1);

namespace App\Domain\Comment;

final class Comment
{
    public function __construct(
        private readonly string $id,
        private readonly string $postId,
        private readonly string $userId,
        private readonly ?string $parentCommentId,
        private readonly string $content,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
        private readonly bool $isDeleted = false
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function postId(): string
    {
        return $this->postId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function parentCommentId(): ?string
    {
        return $this->parentCommentId;
    }

    public function content(): string
    {
        return $this->content;
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'postId' => $this->postId,
            'userId' => $this->userId,
            'parentCommentId' => $this->parentCommentId,
            'content' => $this->content,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}