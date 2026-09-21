<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ProposalSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Proposal $proposal,
        public readonly User $actor,
    ) {}
}
