<?php

declare(strict_types=1);

namespace App\Infrastructure\GraphQL\Cache;

use GraphQL\Language\AST\DocumentNode;

final class LruAstCache
{
    /** @var array<string, DocumentNode> */
    private array $items = [];

    /** @var array<int, string> */
    private array $order = [];

    public function __construct(private readonly int $capacity = 128)
    {
    }

    public function get(string $key): ?DocumentNode
    {
        if (!isset($this->items[$key])) {
            return null;
        }

        $this->touch($key);
        return $this->items[$key];
    }

    public function put(string $key, DocumentNode $value): void
    {
        if (isset($this->items[$key])) {
            $this->items[$key] = $value;
            $this->touch($key);
            return;
        }

        if (count($this->items) >= $this->capacity) {
            $oldest = array_shift($this->order);
            if ($oldest !== null) {
                unset($this->items[$oldest]);
            }
        }

        $this->items[$key] = $value;
        $this->order[] = $key;
    }

    private function touch(string $key): void
    {
        $index = array_search($key, $this->order, true);
        if ($index !== false) {
            unset($this->order[$index]);
            $this->order = array_values($this->order);
        }

        $this->order[] = $key;
    }
}
