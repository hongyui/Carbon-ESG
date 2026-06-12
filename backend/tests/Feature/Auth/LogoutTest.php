<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out an authenticated user and returns 204', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/logout');

    $response->assertNoContent();
});

it('rejects unauthenticated logout with 401', function () {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});
