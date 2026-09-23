<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Checks permissions only. Roles are just permission bundles (see App\Enums\Role).
 */
final class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::ViewAnyProposals, Permission::ViewOwnProposals]);
    }

    /**
     * Denied as 404, not 403: a speaker must not be able to tell someone else's proposal
     * from an id that doesn't exist.
     */
    public function view(User $user, Proposal $proposal): Response
    {
        $allowed = $user->can(Permission::ViewAnyProposals)
            || ($user->can(Permission::ViewOwnProposals) && $proposal->author()->is($user));

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateProposals);
    }

    public function review(User $user, Proposal $proposal): Response
    {
        return $this->decide($user, $proposal, Permission::ReviewProposals);
    }

    public function changeStatus(User $user, Proposal $proposal): Response
    {
        return $this->decide($user, $proposal, Permission::ChangeProposalStatus);
    }

    public function viewReviews(User $user, Proposal $proposal): bool
    {
        return $user->can(Permission::ViewReviews) && $this->view($user, $proposal)->allowed();
    }

    public function downloadAttachment(User $user, Proposal $proposal): Response
    {
        return $this->view($user, $proposal);
    }

    /**
     * Invisible proposals are denied as 404 so no endpoint reveals which ids exist;
     * a visible proposal the user simply may not act on is a plain 403.
     */
    private function decide(User $user, Proposal $proposal, Permission $permission): Response
    {
        if ($this->view($user, $proposal)->denied()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission) ? Response::allow() : Response::deny();
    }
}
