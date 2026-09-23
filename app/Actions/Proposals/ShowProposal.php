<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ShowProposal
{
    /**
     * Loads a proposal for display. Reviews are fetched only for viewers allowed to read them,
     * which is decided here so the controller and the resource share one answer.
     */
    public function handle(User $viewer, Proposal $proposal): Proposal
    {
        return $proposal->loadDetails(
            withReviews: Gate::forUser($viewer)->allows('viewReviews', $proposal),
        );
    }
}
