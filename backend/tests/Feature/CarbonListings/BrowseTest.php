<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only approved listings, newest first, paginated 12 per page', function () {
    $user = User::factory()->create();

    // Approved (should appear)
    $approvedNewer = CarbonListing::factory()->approved()->create([
        'created_at' => now(),
    ]);
    $approvedOlder = CarbonListing::factory()->approved()->create([
        'created_at' => now()->subDay(),
    ]);

    // Non-approved (should NOT appear)
    CarbonListing::factory()->create(); // pending
    CarbonListing::factory()->rejected()->create();
    CarbonListing::factory()->recalled()->create();
    CarbonListing::factory()->sold()->create();

    $response = $this->actingAs($user)->getJson('/api/carbon-listings');

    $response->assertOk()
        ->assertJsonPath('per_page', 12)
        ->assertJsonPath('current_page', 1)
        ->assertJsonCount(2, 'data');

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$approvedNewer->id, $approvedOlder->id]);
});

it('paginates approved listings across multiple pages', function () {
    $user = User::factory()->create();
    CarbonListing::factory()->approved()->count(15)->create();

    $response = $this->actingAs($user)->getJson('/api/carbon-listings?page=2');

    $response->assertOk()
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('last_page', 2)
        ->assertJsonCount(3, 'data');
});

it('rejects anonymous browse with 401', function () {
    $response = $this->getJson('/api/carbon-listings');

    $response->assertUnauthorized();
});
