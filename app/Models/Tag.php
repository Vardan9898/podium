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
        'normalized_name',
    ];

    /**
     * Canonical display form: trimmed, inner whitespace collapsed.
     */
    public static function normalizeName(string $name): string
    {
        return Str::squish($name);
    }

    /**
     * Identity used for de-duplication: normalised and case-folded, nothing else dropped,
     * so "C", "C#" and "C++" stay distinct while "Laravel" and " LARAVEL " collide.
     */
    public static function keyFor(string $name): string
    {
        return mb_strtolower(self::normalizeName($name));
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
