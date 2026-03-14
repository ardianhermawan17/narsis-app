<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class DuplicateImageDetectedException extends AppException
{
    public function __construct(string $message = 'Uploaded image is considered duplicate by fingerprint validation.')
    {
        parent::__construct($message, 409);
    }
}