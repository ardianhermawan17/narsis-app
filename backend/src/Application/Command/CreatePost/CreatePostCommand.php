<?php

declare(strict_types=1);

namespace App\Application\Command\CreatePost;

final class CreatePostCommand
{
    /**
     * @param array<int, array{imageBase64:string,mimeType?:string,altText?:string,isPrimary?:bool}> $images
     */
    public function __construct(
        public readonly string $userId,
        public readonly ?string $caption,
        public readonly string $visibility,
        public readonly array $images
    ) {
    }
}