<?php

namespace App\Dto\Api\Common;

class ErrorResponseDto
{
    public function __construct(
        public string $message,
        public string $error,
        public int $statusCode,
    ) {
    }
}