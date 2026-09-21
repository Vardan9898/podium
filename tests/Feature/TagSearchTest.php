<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tag;

it('searches tags by name for autocomplete', function (): void {
    Tag::factory()->named('Laravel')->create();
    Tag::factory()->named('Vue.js')->create();

    $this->actingAs(userWithRole(Role::Speaker))->getJson('/api/tags?search=lara')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Laravel');
});

it('returns at most the configured number of tags, ordered by name', function (): void {
    Tag::factory()->count(25)->create();

    $response = $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/tags')->assertJsonCount(20, 'data');

    $names = $response->json('data.*.name');
    expect($names)->toBe(collect($names)->sort()->values()->all());
});
