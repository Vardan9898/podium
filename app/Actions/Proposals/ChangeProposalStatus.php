<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Events\ProposalStatusChanged;
use App\Models\Proposal;
use App\Models\User;

final class ChangeProposalStatus
{
    public function handle(User $actor, Proposal $proposal, ProposalStatus $status): Proposal
    {
        $previous = $proposal->status;

        if ($previous === $status) {
            return $proposal;
        }

        $proposal->update(['status' => $status]);

        ProposalStatusChanged::dispatch($proposal, $previous, $actor);

        return $proposal;
    }
}
