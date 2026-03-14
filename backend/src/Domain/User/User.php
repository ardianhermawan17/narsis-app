<?php

declare(strict_types=1);

namespace App\Domain\User;

final class User
{
    public function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
        private readonly ?string $displayName = null,
        private readonly ?string $bio = null,
        private readonly ?string $profileImageId = null
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function bio(): ?string
    {
        return $this->bio;
    }

    public function profileImageId(): ?string
    {
        return $this->profileImageId;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * @return array{id:string,username:string,email:string,displayName:string|null,bio:string|null,createdAt:string,updatedAt:string}
     */
    public function toPublicArray(): array
    {
        return [
            'id'          => $this->id,
            'username'    => $this->username,
            'email'       => $this->email,
            'displayName' => $this->displayName,
            'bio'         => $this->bio,
            'createdAt'   => $this->createdAt->format(DATE_ATOM),
            'updatedAt'   => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
