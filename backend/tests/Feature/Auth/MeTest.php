<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Demo',
        'email' => 'me@example.com',
    ]);

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'me@example.com');
});

it('rejects anonymous access with 401', function () {
    $response = $this->getJson('/api/me');

    $response->assertUnauthorized();
});
