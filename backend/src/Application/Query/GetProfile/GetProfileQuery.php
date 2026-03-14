<?php

declare(strict_types=1);

namespace App\Application\Query\GetProfile;

final class GetProfileQuery
{
    public function __construct(public readonly string $userId)
    {
    }
}
