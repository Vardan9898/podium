<?php

declare(strict_types=1);

use App\Actions\Proposals\ReviewProposal;
use App\Data\ReviewData;
use App\Enums\Role;
use App\Events\ProposalReviewed;
use App\Models\Proposal;
use Illuminate\Support\Facades\Event;

it('upserts the reviewer\'s single review and reports whether it was new', function (): void {
    Event::fake([ProposalReviewed::class]);
    $reviewer = userWithRole(Role::Reviewer);
    $proposal = Proposal::factory()->create();
    $action = app(ReviewProposal::class);

    $created = $action->handle($reviewer, $proposal, new ReviewData(3, 'Meh'));
    $updated = $action->handle($reviewer, $proposal, new ReviewData(8, 'Better'));

    expect($created->wasRecentlyCreated)->toBeTrue()
        ->and($updated->wasRecentlyCreated)->toBeFalse()
        ->and($updated->id)->toBe($created->id)
        ->and($proposal->reviews()->sole()->only('rating', 'comment'))->toBe(['rating' => 8, 'comment' => 'Better']);
    Event::assertDispatched(ProposalReviewed::class, fn (ProposalReviewed $e) => $e->actor->is($reviewer));
});
