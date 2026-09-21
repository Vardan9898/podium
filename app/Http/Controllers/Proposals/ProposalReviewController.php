<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Actions\Proposals\ReviewProposal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposals\ReviewProposalRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final class ProposalReviewController extends Controller
{
    /**
     * Create or update your review of a proposal.
     *
     * Each reviewer has at most one review per proposal; sending again overwrites it.
     * Responds 201 when the review is created and 200 when it is updated.
     */
    public function __invoke(
        ReviewProposalRequest $request,
        #[CurrentUser] User $user,
        Proposal $proposal,
        ReviewProposal $reviewProposal,
    ): JsonResponse {
        $review = $reviewProposal->handle($user, $proposal, $request->toData());

        return (new ReviewResource($review->load('author')))
            ->response()
            ->setStatusCode($review->wasRecentlyCreated ? 201 : 200);
    }
}
