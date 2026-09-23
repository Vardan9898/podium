<?php

declare(strict_types=1);

use App\Actions\Notifications\ListNotifications;
use App\Data\ProposalActivity;
use App\Enums\ProposalActivityType;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;

function sendNotifications(User $user, int $times): void
{
    $proposal = Proposal::factory()->create();

    foreach (range(1, $times) as $i) {
        $user->notifyNow(new ProposalActivityNotification(
            ProposalActivity::for(ProposalActivityType::Submitted, $proposal, $proposal->author, "note {$i}"),
        ), ['database']);
    }
}

it('returns only the given user\'s notifications, newest first, paginated by config', function (): void {
    config(['proposals.pagination.notifications_per_page' => 2]);
    $user = userWithRole(Role::Reviewer);
    sendNotifications($user, 3);
    sendNotifications(userWithRole(Role::Reviewer), 2);

    $page = app(ListNotifications::class)->handle($user);

    expect($page->total())->toBe(3)
        ->and($page->perPage())->toBe(2)
        ->and($page->items())->toHaveCount(2)
        ->and($page->items()[0]->data['message'])->toBe('note 3');
});
