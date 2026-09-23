<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Actions\Proposals\ListProposals;
use App\Actions\Proposals\ShowProposal;
use App\Actions\Proposals\SubmitProposal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposals\IndexProposalsRequest;
use App\Http\Requests\Proposals\StoreProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProposalController extends Controller
{
    /**
     * List proposals visible to the current user.
     *
     * Speakers only ever see their own proposals.
     */
    public function index(
        IndexProposalsRequest $request,
        #[CurrentUser] User $user,
        ListProposals $listProposals,
    ): AnonymousResourceCollection {
        return ProposalResource::collection(
            $listProposals->handle($user, $request->toData()),
        );
    }

    /**
     * Submit a proposal.
     *
     * Send as `multipart/form-data` when attaching a PDF. Unknown tag names are created.
     */
    public function store(
        StoreProposalRequest $request,
        #[CurrentUser] User $user,
        SubmitProposal $submitProposal,
    ): JsonResponse {
        $proposal = $submitProposal->handle($user, $request->toData());

        return (new ProposalResource($proposal->loadDetails(withReviews: false)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a proposal.
     *
     * `reviews` is only included for users allowed to read reviews.
     */
    public function show(#[CurrentUser] User $user, Proposal $proposal, ShowProposal $showProposal): ProposalResource
    {
        return new ProposalResource($showProposal->handle($user, $proposal));
    }
}
