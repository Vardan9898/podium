<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Actions\Proposals\SummariseProposals;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProposalSummaryResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final class ProposalSummaryController extends Controller
{
    /**
     * Counts for the dashboard, scoped to what you may see.
     *
     * Speakers get their own proposals by status; reviewers also get how many they have
     * not reviewed yet.
     */
    public function __invoke(#[CurrentUser] User $user, SummariseProposals $summarise): ProposalSummaryResource
    {
        return new ProposalSummaryResource($summarise->handle($user));
    }
}
