<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ReviewData
{
    public function __construct(
        public int $rating,
        public string $comment,
    ) {}
}
