<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Data\ReviewData;
use App\Events\ProposalReviewed;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;

final class ReviewProposal
{
    /**
     * One review per reviewer per proposal: submitting again updates it.
     * Re-sending an identical review changes nothing and notifies no one.
     */
    public function handle(User $reviewer, Proposal $proposal, ReviewData $data): Review
    {
        $review = $proposal->reviews()->updateOrCreate(
            ['user_id' => $reviewer->id],
            ['rating' => $data->rating, 'comment' => $data->comment],
        );

        if ($review->wasRecentlyCreated || $review->wasChanged()) {
            ProposalReviewed::dispatch($proposal, $review, $reviewer, $review->wasRecentlyCreated);
        }

        return $review;
    }
}
