<?php

declare(strict_types=1);

use App\Actions\Notifications\MarkNotificationsRead;
use App\Data\NotificationSelection;
use App\Data\ProposalActivity;
use App\Enums\ProposalActivityType;
use App\Enums\Role;
use App\Models\Proposal;
use App\Notifications\ProposalActivityNotification;

it('marks only the given ids, or all when ids is null', function (): void {
    $user = userWithRole(Role::Admin);
    $proposal = Proposal::factory()->create();
    foreach (range(1, 3) as $i) {
        $user->notifyNow(new ProposalActivityNotification(
            ProposalActivity::for(ProposalActivityType::Submitted, $proposal, $proposal->author, 'x'),
        ), ['database']);
    }
    $action = app(MarkNotificationsRead::class);

    $action->handle($user, new NotificationSelection([$user->notifications()->firstOrFail()->id]));
    expect($user->unreadNotifications()->count())->toBe(2);

    $action->handle($user, new NotificationSelection(null));
    expect($user->unreadNotifications()->count())->toBe(0);
});
