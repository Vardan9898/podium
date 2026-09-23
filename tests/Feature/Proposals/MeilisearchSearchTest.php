<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Proposal;
use Illuminate\Support\Facades\Artisan;

/*
 * The hosted-engine path (ids from the engine, filtering in SQL) against a real Meilisearch.
 * Skipped unless one is reachable; CI runs it with the service defined in ci.yml.
 */
pest()->group('meilisearch');

function meilisearchReachable(): bool
{
    $url = (string) config('scout.meilisearch.host');
    $host = (string) (parse_url($url, PHP_URL_HOST) ?: '127.0.0.1');
    $port = (int) (parse_url($url, PHP_URL_PORT) ?: 7700);

    $socket = @fsockopen($host, $port, $errno, $error, 1.0);

    if ($socket === false) {
        return false;
    }

    fclose($socket);

    return true;
}

/**
 * Meilisearch indexes asynchronously, and querying an index it has not created yet throws,
 * so both "not ready" cases are treated the same: wait and look again.
 */
function untilIndexed(callable $check, int $tries = 25): bool
{
    foreach (range(1, $tries) as $attempt) {
        if (rescue($check, false, report: false)) {
            return true;
        }

        usleep(200_000);
    }

    return false;
}

beforeEach(function (): void {
    config([
        'scout.driver' => 'meilisearch',
        'scout.prefix' => 'podium_test_'.getmypid().'_',
    ]);
    rescue(fn () => Artisan::call('scout:delete-index', ['name' => config('scout.prefix').'proposals']), report: false);
})->skip(fn (): bool => ! meilisearchReachable(), 'Meilisearch is not running');

afterEach(function (): void {
    rescue(fn () => Artisan::call('scout:delete-index', ['name' => config('scout.prefix').'proposals']), report: false);
});

it('finds proposals through the engine, with typos, and still applies SQL filters', function (): void {
    $speaker = userWithRole(Role::Speaker);
    $mine = Proposal::factory()->for($speaker, 'author')->approved()->create(['title' => 'Mastering Laravel Queues']);
    Proposal::factory()->approved()->create(['title' => 'Laravel for beginners']);
    Proposal::factory()->create(['title' => 'Rust in production']);

    expect(untilIndexed(fn (): bool => Proposal::search('laravel')->keys()->count() === 2))->toBeTrue();

    // Typo tolerance is the reason to run a hosted engine at all.
    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals?search=laravl')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    // Visibility and filters still come from SQL, not the engine.
    $this->actingAs($speaker)->getJson('/api/proposals?search=laravel')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $mine->id);

    $this->actingAs(userWithRole(Role::Admin))->getJson('/api/proposals?search=laravel&status=pending')
        ->assertJsonPath('meta.total', 0);
});

it('indexes a new proposal and drops a deleted one', function (): void {
    $proposal = Proposal::factory()->create(['title' => 'Edge caching explained']);

    expect(untilIndexed(fn (): bool => Proposal::search('edge caching')->keys()->contains($proposal->id)))->toBeTrue();

    $proposal->delete();

    expect(untilIndexed(fn (): bool => Proposal::search('edge caching')->keys()->isEmpty()))->toBeTrue();
});
