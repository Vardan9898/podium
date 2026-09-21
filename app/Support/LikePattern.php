<?php

declare(strict_types=1);

namespace App\Support;

final class LikePattern
{
    /**
     * Builds a "contains" pattern where user input is matched literally.
     */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
