<?php

declare(strict_types=1);

use App\Data\ProposalActivity;
use App\Enums\ProposalActivityType;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;

function notify(User $user, int $times = 1): void
{
    $proposal = Proposal::factory()->create();

    foreach (range(1, $times) as $i) {
        $user->notifyNow(new ProposalActivityNotification(
            ProposalActivity::for(ProposalActivityType::Submitted, $proposal, $proposal->author, 'New proposal.'),
        ), ['database']);
    }
}

it('lists the user\'s own notifications with an unread count', function (): void {
    $user = userWithRole(Role::Reviewer);
    notify($user, 3);
    notify(userWithRole(Role::Reviewer));

    $this->actingAs($user)->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('unread_count', 3)
        ->assertJsonPath('data.0.type', ProposalActivityType::Submitted->value)
        ->assertJsonPath('data.0.data.message', 'New proposal.')
        ->assertJsonPath('data.0.read_at', null);
});

it('marks selected notifications as read', function (): void {
    $user = userWithRole(Role::Reviewer);
    notify($user, 3);
    $id = $user->notifications()->firstOrFail()->id;

    $this->actingAs($user)->postJson('/api/notifications/read', ['ids' => [$id]])->assertNoContent();

    expect($user->unreadNotifications()->count())->toBe(2)
        ->and($user->notifications()->findOrFail($id)->read_at)->not->toBeNull();
});

it('marks all notifications as read when no ids are given', function (): void {
    $user = userWithRole(Role::Admin);
    notify($user, 2);

    $this->actingAs($user)->postJson('/api/notifications/read')->assertNoContent();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('cannot touch another user\'s notifications', function (): void {
    $owner = userWithRole(Role::Admin);
    notify($owner);
    $id = $owner->notifications()->firstOrFail()->id;

    $this->actingAs(userWithRole(Role::Admin))->postJson('/api/notifications/read', ['ids' => [$id]])->assertNoContent();

    expect($owner->unreadNotifications()->count())->toBe(1);
});

it('validates notification ids', function (): void {
    $this->actingAs(userWithRole(Role::Admin))
        ->postJson('/api/notifications/read', ['ids' => ['not-a-uuid']])
        ->assertJsonValidationErrors('ids.0');
});
