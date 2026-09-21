<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProposalReviewed implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Proposal $proposal,
        public readonly Review $review,
        public readonly User $actor,
        public readonly bool $isNewReview,
    ) {}
}
