<?php

declare(strict_types=1);

use App\Actions\Tags\SearchTags;
use App\Models\Tag;

it('matches part of a name, ignoring case and surrounding space', function (): void {
    Tag::factory()->named('Laravel')->create();
    Tag::factory()->named('Vue.js')->create();

    expect(app(SearchTags::class)->handle('  LARA ')->pluck('name')->all())->toBe(['Laravel']);
});

it('orders by name before applying the limit', function (): void {
    $limit = 5;
    config(['proposals.tags.autocomplete_limit' => $limit]);
    foreach (['Zulu', 'Alpha', 'Mike', 'Bravo', 'Yankee', 'Charlie', 'Delta'] as $name) {
        Tag::factory()->named($name)->create();
    }

    $names = app(SearchTags::class)->handle(null)->pluck('name')->all();

    expect($names)->toHaveCount($limit)
        ->and($names)->toBe(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Mike']);
});

it('returns everything up to the limit when no term is given', function (): void {
    Tag::factory()->count(3)->create();

    expect(app(SearchTags::class)->handle(''))->toHaveCount(3);
});
