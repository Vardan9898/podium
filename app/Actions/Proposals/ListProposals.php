<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Data\ProposalFilters;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            ->withSummary()
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
