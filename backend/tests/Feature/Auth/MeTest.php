<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user with role flags', function () {
    $user = User::factory()->create([
        'name' => 'Demo',
        'email' => 'me@example.com',
    ]);

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'me@example.com')
        ->assertJsonPath('user.isAdmin', false)
        ->assertJsonPath('user.isSeller', false)
        ->assertJsonPath('user.hasPurchased', false);
});

it('reports isAdmin true for admin users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson('/api/me');

    $response->assertOk()->assertJsonPath('user.isAdmin', true);
});

it('reports isSeller true once a listing exists for the user', function () {
    $user = User::factory()->create();
    CarbonListing::factory()->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk()->assertJsonPath('user.isSeller', true);
});

it('reports hasPurchased true once a purchase row exists for the user', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->sold()->create();
    CarbonPurchase::factory()->create([
        'carbon_listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/me');

    $response->assertOk()->assertJsonPath('user.hasPurchased', true);
});

it('rejects anonymous access with 401', function () {
    $response = $this->getJson('/api/me');

    $response->assertUnauthorized();
});
