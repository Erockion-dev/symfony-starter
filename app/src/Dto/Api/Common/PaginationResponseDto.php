<?php

namespace App\Dto\Api\Common;

class PaginationResponseDto
{
    public function __construct(
        public int $total,
        public int $page,
        public int $limit,
        public int $totalPages,
    ) {
    }
}
