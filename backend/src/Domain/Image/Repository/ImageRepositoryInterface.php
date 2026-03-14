<?php

declare(strict_types=1);

namespace App\Domain\Image\Repository;

use App\Domain\Image\Image;
use App\Domain\Image\ImageFingerprint;

interface ImageRepositoryInterface
{
    public function save(Image $image): void;

    public function saveFingerprint(ImageFingerprint $fingerprint): void;

    /**
     * @return array<int, array{id:string,imageId:string,hashValue:string,distanceThreshold:int}>
     */
    public function listFingerprintsByAlgorithm(string $algorithm): array;

    /**
     * @return array<int, Image>
     */
    public function findByImageable(string $imageableType, string $imageableId): array;
}