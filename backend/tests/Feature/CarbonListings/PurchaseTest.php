<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('happy path creates a purchase row and flips the listing to sold atomically', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->approved()->create(['price_twd' => 100_000]);

    $response = $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase");

    $response->assertCreated()
        ->assertJsonPath('purchase.carbon_listing_id', $listing->id)
        ->assertJsonPath('purchase.buyer_id', $buyer->id);

    expect($listing->fresh()->status)->toBe('sold');
    expect(CarbonPurchase::where('carbon_listing_id', $listing->id)->count())->toBe(1);
});

it('snapshots the listing price into the purchase row', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->approved()->create(['price_twd' => 250_000]);

    $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase")
        ->assertCreated();

    expect((float) CarbonPurchase::first()->price_twd)->toBe(250_000.00);
});

it('owner cannot purchase their own listing', function () {
    $owner = User::factory()->create();
    $listing = CarbonListing::factory()->approved()->for($owner)->create();

    $response = $this->actingAs($owner)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase");

    $response->assertForbidden();
    expect($listing->fresh()->status)->toBe('approved');
    expect(CarbonPurchase::count())->toBe(0);
});

it('cannot purchase a non-approved listing', function () {
    $buyer = User::factory()->create();
    $pending = CarbonListing::factory()->create();
    $sold = CarbonListing::factory()->sold()->create();

    $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$pending->id}/purchase")
        ->assertForbidden();

    $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$sold->id}/purchase")
        ->assertForbidden();
});

it('rejects anonymous purchase with 401', function () {
    $listing = CarbonListing::factory()->approved()->create();

    $response = $this->postJson("/api/carbon-listings/{$listing->id}/purchase");

    $response->assertUnauthorized();
});
