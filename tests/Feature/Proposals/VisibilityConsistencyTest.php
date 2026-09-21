<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;

/*
 * Proposal::visibleTo() (lists) and ProposalPolicy::view() (single records) are two
 * implementations of one rule. This proves they never disagree, for every role.
 */
it('keeps the list scope and the view policy in agreement', function (Role $role): void {
    $viewer = userWithRole($role);
    Proposal::factory()->count(3)->for($viewer, 'author')->create();
    Proposal::factory()->count(4)->create();

    $visible = Proposal::query()->visibleTo($viewer)->pluck('id')->all();

    Proposal::query()->each(function (Proposal $proposal) use ($viewer, $visible): void {
        expect($viewer->can('view', $proposal))->toBe(in_array($proposal->id, $visible, true));
    });
})->with(Role::cases());

it('shows nothing to a user with no proposal permissions', function (): void {
    Proposal::factory()->count(2)->create();

    expect(Proposal::query()->visibleTo(User::factory()->create())->count())->toBe(0);
});
