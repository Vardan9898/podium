<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\LikePattern;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

final class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Canonical display form: trimmed, inner whitespace collapsed.
     */
    public static function normalizeName(string $name): string
    {
        return Str::squish($name);
    }

    /**
     * Case-insensitive identity used for de-duplication.
     */
    public static function slugFor(string $name): string
    {
        return Str::slug(self::normalizeName($name));
    }

    /** @return BelongsToMany<Proposal, $this> */
    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class);
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = self::normalizeName((string) $term);

        if ($term !== '') {
            $query->whereLike('name', LikePattern::contains($term));
        }
    }
}
