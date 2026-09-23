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
        // createOrFirst survives two concurrent submissions from the same reviewer: the loser of
        // the unique-index race re-reads the winner's row instead of erroring.
        $review = $proposal->reviews()->createOrFirst(
            ['user_id' => $reviewer->id],
            ['rating' => $data->rating, 'comment' => $data->comment],
        );

        if (! $review->wasRecentlyCreated) {
            $review->fill(['rating' => $data->rating, 'comment' => $data->comment])->save();
        }

        if ($review->wasRecentlyCreated || $review->wasChanged()) {
            ProposalReviewed::dispatch($proposal, $review, $reviewer, $review->wasRecentlyCreated);
        }

        return $review;
    }
}
