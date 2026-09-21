<?php

declare(strict_types=1);

use App\Actions\Tags\SyncProposalTags;
use App\Models\Proposal;
use App\Models\Tag;

it('creates missing tags, reuses existing ones and de-duplicates by slug', function (): void {
    $existing = Tag::factory()->named('Laravel')->create();
    $proposal = Proposal::factory()->create();

    app(SyncProposalTags::class)->handle($proposal, ['LARAVEL', ' Vue ', 'vue', '   ']);

    expect(Tag::query()->pluck('name')->sort()->values()->all())->toBe(['Laravel', 'Vue'])
        ->and($proposal->tags()->pluck('tags.id'))->toContain($existing->id)->toHaveCount(2);
});

it('replaces previous tags and detaches everything when given none', function (): void {
    $proposal = Proposal::factory()->withTags(3)->create();

    app(SyncProposalTags::class)->handle($proposal, ['Solo']);
    expect($proposal->tags()->pluck('name')->all())->toBe(['Solo']);

    app(SyncProposalTags::class)->handle($proposal, []);
    expect($proposal->tags()->count())->toBe(0);
});
