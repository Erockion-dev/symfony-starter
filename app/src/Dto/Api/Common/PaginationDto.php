<?php

namespace App\Dto\Api\Common;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationDto
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 10;
}
