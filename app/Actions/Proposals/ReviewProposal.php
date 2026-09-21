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
     */
    public function handle(User $reviewer, Proposal $proposal, ReviewData $data): Review
    {
        $review = $proposal->reviews()->updateOrCreate(
            ['user_id' => $reviewer->id],
            ['rating' => $data->rating, 'comment' => $data->comment],
        );

        ProposalReviewed::dispatch($proposal, $review, $reviewer);

        return $review;
    }
}
