<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 * Every API route × every kind of caller, keyed by route name.
 * The proposal belongs to a *different* speaker, so the speaker column proves
 * speakers cannot reach other people's work (404: they can't even learn it exists).
 *
 * Expected status codes: [guest, speaker, reviewer, admin].
 */
function authorizationMatrix(): array
{
    return [
        'config' => [[], [200, 200, 200, 200]],
        'register' => [[
            'name' => 'New', 'email' => 'new@example.com', 'role' => Role::Speaker->value,
            'password' => 'password1', 'password_confirmation' => 'password1',
        ], [201, 403, 403, 403]],
        'login' => [['email' => 'nobody@example.com', 'password' => 'wrong'], [422, 403, 403, 403]],
        'logout' => [[], [401, 204, 204, 204]],
        'me' => [[], [401, 200, 200, 200]],
        'proposals.index' => [[], [401, 200, 200, 200]],
        'proposals.store' => [['title' => 'T', 'description' => 'D'], [401, 201, 403, 403]],
        'proposals.show' => [[], [401, 404, 200, 200]],
        'proposals.attachment' => [[], [401, 404, 200, 200]],
        'proposals.status' => [['status' => ProposalStatus::Approved->value], [401, 403, 403, 200]],
        'proposals.review' => [['rating' => 5, 'comment' => 'Nice'], [401, 403, 201, 403]],
        'tags.index' => [[], [401, 200, 200, 200]],
        'notifications.index' => [[], [401, 200, 200, 200]],
        'notifications.read' => [[], [401, 204, 204, 204]],
    ];
}

dataset('endpoints', collect(authorizationMatrix())->map(fn (array $row, string $route) => [$route, ...$row])->all());

dataset('actors', [
    'guest' => [0, null],
    'speaker' => [1, Role::Speaker],
    'reviewer' => [2, Role::Reviewer],
    'admin' => [3, Role::Admin],
]);

it('enforces access rules', function (string $routeName, array $payload, array $expected, int $column, ?Role $role): void {
    Storage::fake('local');
    Storage::disk('local')->put('proposals/matrix.pdf', '%PDF-1.4');
    $proposal = Proposal::factory()->withAttachment('proposals/matrix.pdf')->create();

    $route = Route::getRoutes()->getByName($routeName);
    $uri = route($routeName, in_array('proposal', $route->parameterNames(), true) ? $proposal : [], false);

    if ($role !== null) {
        $this->actingAs(userWithRole($role));
    }

    $this->json($route->methods()[0], $uri, $payload)->assertStatus($expected[$column]);
})->with('endpoints')->with('actors');

it('covers every API route', function (): void {
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RouteDefinition $route) => str_starts_with($route->uri(), 'api/'))
        ->reject(fn (RouteDefinition $route) => $route->uri() === 'api/broadcasting/auth') // BroadcastChannelTest
        ->map(fn (RouteDefinition $route) => $route->getName());

    expect($apiRoutes->sort()->values()->all())->toBe(collect(authorizationMatrix())->keys()->sort()->values()->all());
});
