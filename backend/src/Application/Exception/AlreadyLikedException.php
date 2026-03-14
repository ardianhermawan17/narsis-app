<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class AlreadyLikedException extends AppException
{
    public function __construct(string $message = 'You already liked this post.')
    {
        parent::__construct($message, 409);
    }
}
