<?php

declare(strict_types=1);

namespace App\Infrastructure\Image\Storage;

final class LocalImageStorage
{
    public function __construct(private readonly string $baseDirectory)
    {
    }

    /**
     * @return array{storageKey:string,sizeBytes:int,width:int|null,height:int|null,mimeType:string}
     */
    public function storeBase64(string $imageBase64, string $imageId, ?string $clientMimeType = null): array
    {
        $binary = base64_decode($imageBase64, true);
        if ($binary === false) {
            throw new \RuntimeException('imageBase64 must be valid base64 content.');
        }

        $mimeType = $clientMimeType !== null && $clientMimeType !== ''
            ? strtolower($clientMimeType)
            : $this->detectMimeType($binary);

        $extension = $this->extensionForMimeType($mimeType);
        $datePath = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y/m/d');
        $relativeDir = 'posts/' . $datePath;
        $storageDir = rtrim($this->baseDirectory, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            throw new \RuntimeException('Failed to create image storage directory.');
        }

        $filename = $imageId . '.' . $extension;
        $absolutePath = $storageDir . DIRECTORY_SEPARATOR . $filename;
        $written = file_put_contents($absolutePath, $binary);
        if ($written === false) {
            throw new \RuntimeException('Failed to persist image data to storage.');
        }

        $width = null;
        $height = null;
        $size = @getimagesizefromstring($binary);
        if (is_array($size)) {
            $width = isset($size[0]) ? (int) $size[0] : null;
            $height = isset($size[1]) ? (int) $size[1] : null;
        }

        return [
            'storageKey' => $relativeDir . '/' . $filename,
            'sizeBytes' => strlen($binary),
            'width' => $width,
            'height' => $height,
            'mimeType' => $mimeType,
        ];
    }

    private function detectMimeType(string $binary): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        return is_string($mime) && $mime !== '' ? strtolower($mime) : 'application/octet-stream';
    }

    private function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
    }
}