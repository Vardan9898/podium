<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Data\ProposalFilters;
use App\Enums\Permission;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListProposals
{
    /**
     * @return LengthAwarePaginator<int, Proposal>
     */
    public function handle(User $viewer, ProposalFilters $filters): LengthAwarePaginator
    {
        return Proposal::query()
            ->visibleTo($viewer)
            ->titleMatches($filters->search)
            ->withAnyTags($filters->tags)
            ->status($filters->status)
            // Only a reviewer has a review queue; for anyone else the flag means nothing.
            ->when(
                $filters->awaitingMyReview && $viewer->can(Permission::ReviewProposals),
                fn (Builder $query) => $query->awaitingReviewBy($viewer),
            )
            ->withSummary()
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
