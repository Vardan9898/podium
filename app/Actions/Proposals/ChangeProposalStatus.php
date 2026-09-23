<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Events\ProposalStatusChanged;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChangeProposalStatus
{
    /**
     * The row is locked so two admins deciding at once are serialised: the second sees the
     * first one's status as "previous" instead of both overwriting "pending".
     */
    public function handle(User $actor, Proposal $proposal, ProposalStatus $status): Proposal
    {
        return DB::transaction(function () use ($actor, $proposal, $status): Proposal {
            $locked = Proposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $previous = $locked->status;

            if ($previous === $status) {
                return $locked;
            }

            $locked->update(['status' => $status]);

            ProposalStatusChanged::dispatch($locked, $previous, $status, $actor);

            return $locked;
        });
    }
}
