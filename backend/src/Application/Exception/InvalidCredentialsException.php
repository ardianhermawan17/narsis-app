<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class InvalidCredentialsException extends AppException
{
    public function __construct(string $message = 'Invalid credentials.')
    {
        parent::__construct($message, 401);
    }
}
