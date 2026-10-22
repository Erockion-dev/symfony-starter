<?php

namespace App\Dto\Api\User;

use App\Dto\Api\Common\PaginationResponseDto;

class UsersResponseDto
{
    /**
     * @param UserResponseDto[] $data
     */
    public function __construct(
        public array $data,
        public PaginationResponseDto $meta,
    ) {
    }
}
