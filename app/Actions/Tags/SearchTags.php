<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

final class SearchTags
{
    /**
     * @return Collection<int, Tag>
     */
    public function handle(?string $term): Collection
    {
        return Tag::query()
            ->search($term)
            ->orderBy('name')
            ->limit(config()->integer('proposals.tags.autocomplete_limit'))
            ->get();
    }
}
