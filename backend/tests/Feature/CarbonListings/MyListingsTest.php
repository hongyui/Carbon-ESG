<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user listings across all statuses', function () {
    $user = User::factory()->create();
    CarbonListing::factory()->for($user)->create();
    CarbonListing::factory()->approved()->for($user)->create();
    CarbonListing::factory()->recalled()->for($user)->create();

    // Other user's listing should not appear
    CarbonListing::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/carbon-listings/mine');

    $response->assertOk();
    expect($response->json('listings'))->toHaveCount(3);
});

it('orders listings newest first', function () {
    $user = User::factory()->create();
    $older = CarbonListing::factory()->for($user)->create([
        'created_at' => now()->subDays(2),
    ]);
    $newer = CarbonListing::factory()->for($user)->create([
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/carbon-listings/mine');

    $ids = collect($response->json('listings'))->pluck('id')->all();
    expect($ids)->toBe([$newer->id, $older->id]);
});

it('rejects anonymous access with 401', function () {
    $response = $this->getJson('/api/carbon-listings/mine');

    $response->assertUnauthorized();
});
