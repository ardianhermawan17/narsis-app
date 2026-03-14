<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Error;

use App\Application\Exception\AppException;

final class HttpErrorHandler
{
    /**
     * @return array{status:int,body:array<string,string>}
     */
    public function handle(\Throwable $throwable): array
    {
        if ($throwable instanceof AppException) {
            return [
                'status' => $throwable->statusCode(),
                'body' => ['error' => $throwable->getMessage()],
            ];
        }

        return [
            'status' => 500,
            'body' => ['error' => 'Server error.'],
        ];
    }
}
