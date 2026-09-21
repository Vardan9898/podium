<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ProposalStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Proposal $proposal,
        public readonly ProposalStatus $previous,
        public readonly User $actor,
    ) {}
}
