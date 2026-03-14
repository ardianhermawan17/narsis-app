<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class IdentityAlreadyExistsException extends AppException
{
    public function __construct(string $message = 'Username or email already exists.')
    {
        parent::__construct($message, 409);
    }
}
