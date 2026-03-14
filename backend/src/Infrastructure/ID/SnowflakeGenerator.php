<?php

declare(strict_types=1);

namespace App\Infrastructure\ID;

final class SnowflakeGenerator
{
    private const SERVER_BITS = 4;
    private const SEQUENCE_BITS = 19;
    private const MAX_SERVER_ID = 15;
    private const MAX_SEQUENCE = 524287;
    private const CUSTOM_EPOCH_MS = 1609459200000;

    private int $lastTimestamp = 0;
    private int $sequence = 0;

    public function __construct(private readonly int $serverId)
    {
        if ($this->serverId < 0 || $this->serverId > self::MAX_SERVER_ID) {
            throw new \InvalidArgumentException('SERVER_ID must be between 0 and 15.');
        }
    }

    public function nextId(): string
    {
        $timestamp = $this->nowMs();

        if ($timestamp < $this->lastTimestamp) {
            throw new \RuntimeException('Clock moved backwards.');
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;
            if ($this->sequence === 0) {
                $timestamp = $this->waitNextMs($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        $ts = $timestamp - self::CUSTOM_EPOCH_MS;
        $id = ($ts << (self::SERVER_BITS + self::SEQUENCE_BITS))
            | ($this->serverId << self::SEQUENCE_BITS)
            | $this->sequence;

        return (string) $id;
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function waitNextMs(int $lastTimestamp): int
    {
        $timestamp = $this->nowMs();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->nowMs();
        }

        return $timestamp;
    }
}
