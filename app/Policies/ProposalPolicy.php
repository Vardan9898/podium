<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Proposal;
use App\Models\User;

/**
 * Checks permissions only. Roles are just permission bundles (see App\Enums\Role).
 */
final class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::ViewAnyProposals, Permission::ViewOwnProposals]);
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $user->can(Permission::ViewAnyProposals)
            || ($user->can(Permission::ViewOwnProposals) && $proposal->author()->is($user));
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateProposals);
    }

    public function review(User $user, Proposal $proposal): bool
    {
        return $user->can(Permission::ReviewProposals) && $this->view($user, $proposal);
    }

    public function changeStatus(User $user, Proposal $proposal): bool
    {
        return $user->can(Permission::ChangeProposalStatus) && $this->view($user, $proposal);
    }

    public function viewReviews(User $user, Proposal $proposal): bool
    {
        return $user->can(Permission::ViewReviews) && $this->view($user, $proposal);
    }

    public function downloadAttachment(User $user, Proposal $proposal): bool
    {
        return $this->view($user, $proposal);
    }
}
