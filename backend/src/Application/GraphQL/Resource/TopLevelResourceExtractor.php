<?php

declare(strict_types=1);

namespace App\Application\GraphQL\Resource;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;

final class TopLevelResourceExtractor
{
    /**
     * @return array<int, string>
     */
    public function extract(DocumentNode $document): array
    {
        $resources = [];

        foreach ($document->definitions as $definition) {
            if (!$definition instanceof OperationDefinitionNode || $definition->selectionSet === null) {
                continue;
            }

            foreach ($definition->selectionSet->selections as $selection) {
                if ($selection instanceof FieldNode) {
                    $resources[] = $selection->name->value;
                }
            }
        }

        return array_values(array_unique($resources));
    }
}
