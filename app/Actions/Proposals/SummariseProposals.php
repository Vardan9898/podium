<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Data\ProposalSummary;
use App\Enums\Permission;
use App\Models\Proposal;
use App\Models\User;

final class SummariseProposals
{
    /**
     * Two aggregate queries, never a full listing: the strip stays cheap on any dataset.
     */
    public function handle(User $viewer): ProposalSummary
    {
        /** @var array<string, int> $counts */
        $counts = Proposal::query()
            ->visibleTo($viewer)
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $awaitingMyReview = $viewer->can(Permission::ReviewProposals)
            ? Proposal::query()->visibleTo($viewer)->awaitingReviewBy($viewer)->count()
            : null;

        return ProposalSummary::fromCounts($counts, $awaitingMyReview);
    }
}
