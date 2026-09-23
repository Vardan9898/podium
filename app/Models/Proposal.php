<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use App\Enums\ProposalStatus;
use App\Support\LikePattern;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property ProposalStatus $status
 */
final class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    use Searchable;

    private const array SUMMARY_RELATIONS = ['author', 'tags'];

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'status',
        'attachment_path',
        'attachment_original_name',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ProposalStatus::Pending->value,
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }

    /**
     * Single-model counterpart of the withSummary() scope, optionally with reviews.
     */
    public function loadDetails(bool $withReviews): self
    {
        $this->load(self::SUMMARY_RELATIONS)
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        if ($withReviews) {
            $this->load(['reviews' => fn (HasMany $reviews) => $reviews->with('author')->latest('updated_at')]);
        }

        return $this;
    }

    /**
     * Relations and aggregates every proposal payload needs, loaded in bulk.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withSummary(Builder $query): void
    {
        $query->with(self::SUMMARY_RELATIONS)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');
    }

    /**
     * Mirrors ProposalPolicy::view() so lists never show what a user could not open.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can(Permission::ViewAnyProposals)) {
            return;
        }

        if ($user->can(Permission::ViewOwnProposals)) {
            $query->whereBelongsTo($user, 'author');

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * Title search. The engine is configuration (SCOUT_DRIVER).
     *
     * On the default "database" engine the match is inlined into the same statement as the
     * visibility, tag and status filters, so totals and paging stay exact for any result size.
     * A hosted engine (Meilisearch) can only return ids, so those are resolved first and then
     * filtered in SQL; that path is capped at proposals.search.max_matches ids per query.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function titleMatches(Builder $query, ?string $term): void
    {
        $term = mb_trim((string) $term);

        if ($term === '') {
            return;
        }

        if (config()->string('scout.driver') === 'database') {
            $query->whereLike('title', LikePattern::contains($term));

            return;
        }

        $query->whereKey(
            self::search($term)->take(config()->integer('proposals.search.max_matches'))->keys(),
        );
    }

    /**
     * Only the title is searchable (per the brief).
     *
     * @return array{title: string}
     */
    public function toSearchableArray(): array
    {
        return ['title' => $this->title];
    }

    /**
     * @param  Builder<self>  $query
     * @param  list<string>  $keys  Normalised tag keys (see Tag::keyFor()).
     */
    #[Scope]
    protected function withAnyTags(Builder $query, array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('normalized_name', $keys));
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function status(Builder $query, ?ProposalStatus $status): void
    {
        if ($status !== null) {
            $query->where('status', $status);
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
        ];
    }
}
