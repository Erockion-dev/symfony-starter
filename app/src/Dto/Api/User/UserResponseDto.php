<?php

namespace App\Dto\Api\User;

class UserResponseDto
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstname,
        public string $lastname,
        public array $roles,
    ) {
    }
}
