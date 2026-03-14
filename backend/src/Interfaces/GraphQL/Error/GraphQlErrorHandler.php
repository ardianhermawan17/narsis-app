<?php

declare(strict_types=1);

namespace App\Interfaces\GraphQL\Error;

use App\Application\Exception\AppException;
use GraphQL\Error\UserError;

final class GraphQlErrorHandler
{
    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function handle(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (AppException $exception) {
            throw new UserError($exception->getMessage());
        } catch (\Throwable) {
            throw new UserError('Server error.');
        }
    }
}
