<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Actions\Proposals\ChangeProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposals\ChangeProposalStatusRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final class ProposalStatusController extends Controller
{
    /**
     * Change a proposal's status.
     */
    public function __invoke(
        ChangeProposalStatusRequest $request,
        #[CurrentUser] User $user,
        Proposal $proposal,
        ChangeProposalStatus $changeStatus,
    ): ProposalResource {
        $proposal = $changeStatus->handle($user, $proposal, $request->newStatus());

        return new ProposalResource($proposal->loadDetails(withReviews: false));
    }
}
