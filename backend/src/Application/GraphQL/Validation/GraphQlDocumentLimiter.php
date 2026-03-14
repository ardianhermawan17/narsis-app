<?php

declare(strict_types=1);

namespace App\Application\GraphQL\Validation;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;

final class GraphQlDocumentLimiter
{
    public function __construct(
        private readonly int $maxDepth = 8,
        private readonly int $maxCost = 300
    ) {
    }

    public function assertLimits(DocumentNode $document): void
    {
        $depth = $this->calculateDepth($document);
        if ($depth > $this->maxDepth) {
            throw new \RuntimeException(sprintf('GraphQL max depth exceeded (%d > %d).', $depth, $this->maxDepth));
        }

        $cost = $this->calculateCost($document);
        if ($cost > $this->maxCost) {
            throw new \RuntimeException(sprintf('GraphQL max cost exceeded (%d > %d).', $cost, $this->maxCost));
        }
    }

    private function calculateDepth(DocumentNode $document): int
    {
        $maxDepth = 0;

        foreach ($document->definitions as $definition) {
            if (!$definition instanceof OperationDefinitionNode || $definition->selectionSet === null) {
                continue;
            }

            foreach ($definition->selectionSet->selections as $selection) {
                if ($selection instanceof FieldNode) {
                    $maxDepth = max($maxDepth, $this->depthForField($selection, 1));
                }
            }
        }

        return $maxDepth;
    }

    private function depthForField(FieldNode $field, int $depth): int
    {
        if ($field->selectionSet === null) {
            return $depth;
        }

        $max = $depth;
        foreach ($field->selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $max = max($max, $this->depthForField($selection, $depth + 1));
            }
        }

        return $max;
    }

    private function calculateCost(DocumentNode $document): int
    {
        $cost = 0;

        foreach ($document->definitions as $definition) {
            if (!$definition instanceof OperationDefinitionNode || $definition->selectionSet === null) {
                continue;
            }

            foreach ($definition->selectionSet->selections as $selection) {
                if ($selection instanceof FieldNode) {
                    $cost += $this->costForField($selection);
                }
            }
        }

        return $cost;
    }

    private function costForField(FieldNode $field): int
    {
        $cost = 1;

        if ($field->selectionSet !== null) {
            foreach ($field->selectionSet->selections as $selection) {
                if ($selection instanceof FieldNode) {
                    $cost += $this->costForField($selection);
                }
            }
        }

        return $cost;
    }
}
