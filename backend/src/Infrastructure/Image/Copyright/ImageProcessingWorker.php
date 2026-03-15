<?php

declare(strict_types=1);

namespace App\Infrastructure\Image\Copyright;

final class ImageProcessingWorker
{
    /**
     * @return array{algorithm:string,hashValue:string,hashBytes:int,distanceThreshold:int}
     */
    public function generateFingerprint(string $binary): array
    {
        // Build a deterministic 2048-bit digest (256 bytes) for PoC fingerprinting.
        $sha512A = hash('sha512', $binary, true);
        $sha512B = hash('sha512', strrev($binary), true);
        $seed = $sha512A . $sha512B;

        $fingerprint = '';
        for ($i = 0; strlen($fingerprint) < 256; $i++) {
            $fingerprint .= hash('sha512', $seed . pack('N', $i), true);
        }

        return [
            'algorithm' => 'pdq',
            'hashValue' => substr($fingerprint, 0, 256),
            'hashBytes' => 256,
            'distanceThreshold' => 5,
        ];
    }

    public function isDuplicate(string $candidateHash, string $existingHash, int $threshold): bool
    {
        return $this->distance($candidateHash, $existingHash) <= $threshold;
    }

    private function distance(string $hashA, string $hashB): int
    {
        if (strlen($hashA) !== strlen($hashB)) {
            throw new \InvalidArgumentException(
                sprintf('Hash length mismatch: %d vs %d bytes', strlen($hashA), strlen($hashB))
            );
        }
        $length = min(strlen($hashA), strlen($hashB));
        $distance = 0;

        for ($i = 0; $i < $length; $i++) {
            $xor = ord($hashA[$i]) ^ ord($hashB[$i]);
            $distance += $this->popcount($xor);
        }

        return $distance;
    }

    private function popcount(int $value): int
    {
        $count = 0;
        while ($value > 0) {
            $count += $value & 1;
            $value >>= 1;
        }

        return $count;
    }
}