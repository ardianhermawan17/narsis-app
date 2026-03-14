<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Schema;

use GraphQL\Type\Schema;

final class SchemaRegistry
{
    /**
     * @param array<string, callable():Schema> $schemaFactories
     * @param array<string, string> $schemaAliases
     */
    public function __construct(
        private readonly array $schemaFactories,
        private readonly string $defaultSchema,
        private readonly array $schemaAliases = []
    ) {
    }

    public function resolve(?string $resource): Schema
    {
        $key = $resource !== null ? strtolower(trim($resource)) : $this->defaultSchema;

        if (isset($this->schemaAliases[$key])) {
            $key = strtolower(trim($this->schemaAliases[$key]));
        }

        if (isset($this->schemaFactories[$key])) {
            return ($this->schemaFactories[$key])();
        }

        return ($this->schemaFactories[$this->defaultSchema])();
    }
}