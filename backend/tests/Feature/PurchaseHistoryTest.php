<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only the authenticated user purchases', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $listingA = CarbonListing::factory()->sold()->create();
    CarbonPurchase::create([
        'carbon_listing_id' => $listingA->id,
        'buyer_id' => $userA->id,
        'price_twd' => 100_000,
    ]);

    $listingB = CarbonListing::factory()->sold()->create();
    CarbonPurchase::create([
        'carbon_listing_id' => $listingB->id,
        'buyer_id' => $userB->id,
        'price_twd' => 200_000,
    ]);

    $response = $this->actingAs($userA)->getJson('/api/purchases');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.buyer_id'))->toBe($userA->id);
});

it('eager-loads the related listing on each purchase row', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->sold()->create(['title' => '台東 鹿野']);
    CarbonPurchase::create([
        'carbon_listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_twd' => 100_000,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/purchases');

    $response->assertOk()
        ->assertJsonPath('data.0.carbon_listing.title', '台東 鹿野');
});

it('rejects anonymous access with 401', function () {
    $response = $this->getJson('/api/purchases');

    $response->assertUnauthorized();
});
