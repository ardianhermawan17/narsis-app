<?php

declare(strict_types=1);

namespace App\Application\Query\GetProfile;

use App\Domain\User\Repository\UserRepositoryInterface;

final class GetProfileHandler
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * @return array{id:string,username:string,email:string,createdAt:string}
     */
    public function handle(GetProfileQuery $query): array
    {
        $user = $this->users->findById($query->userId);
        if ($user === null) {
            throw new \RuntimeException('User not found.');
        }

        return $user->toPublicArray();
    }
}
