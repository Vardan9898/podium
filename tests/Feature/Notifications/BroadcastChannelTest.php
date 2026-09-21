<?php

declare(strict_types=1);

use App\Enums\Role;
use Illuminate\Broadcasting\BroadcastManager;

beforeEach(function (): void {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);
    app(BroadcastManager::class)->purge('reverb');
    require base_path('routes/channels.php');
});

it('authorises a user for their own private channel only', function (): void {
    $user = userWithRole(Role::Speaker);
    $other = userWithRole(Role::Speaker);

    $this->actingAs($user)
        ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$user->id}"])
        ->assertOk()
        ->assertJsonStructure(['auth']);

    $this->actingAs($user)
        ->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$other->id}"])
        ->assertForbidden();
});

it('rejects guests', function (): void {
    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-App.Models.User.1'])
        ->assertUnauthorized();
});
