<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class NotLikedException extends AppException
{
    public function __construct()
    {
        parent::__construct('You have not liked this post.', 409);
    }
}