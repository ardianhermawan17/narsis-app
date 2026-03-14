<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Error;

use App\Application\Exception\AppException;

final class GraphQlTransportErrorHandler
{
    /**
     * @return array{status:int,body:array{errors:array<int,array{message:string,extensions:array{code:string}}>,data:null}}
     */
    public function handle(\Throwable $throwable): array
    {
        if ($throwable instanceof AppException) {
            return [
                'status' => $throwable->statusCode(),
                'body' => [
                    'errors' => [[
                        'message' => $throwable->getMessage(),
                        'extensions' => [
                            'code' => 'APPLICATION_ERROR',
                        ],
                    ]],
                    'data' => null,
                ],
            ];
        }

        return [
            'status' => 400,
            'body' => [
                'errors' => [[
                    'message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : 'GraphQL request error.',
                    'extensions' => [
                        'code' => 'GRAPHQL_REQUEST_ERROR',
                    ],
                ]],
                'data' => null,
            ],
        ];
    }
}
