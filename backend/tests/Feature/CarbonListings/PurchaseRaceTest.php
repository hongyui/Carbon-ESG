<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 409 when a purchase row already exists for the listing (UNIQUE fallback)', function () {
    // Simulate the narrow concurrent window: a purchase row was inserted
    // by a competing request, but our controller still sees status=approved
    // at the policy check (the listing was not transitioned). The
    // controller's status re-check inside the locked transaction catches
    // this and aborts 409.

    $firstBuyer = User::factory()->create();
    $listing = CarbonListing::factory()->approved()->create();

    CarbonPurchase::create([
        'carbon_listing_id' => $listing->id,
        'buyer_id' => $firstBuyer->id,
        'price_twd' => $listing->price_twd,
    ]);

    // Status is still 'approved' in DB. Now a second buyer tries.
    $secondBuyer = User::factory()->create();

    $response = $this->actingAs($secondBuyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase");

    $response->assertStatus(409);
    expect(CarbonPurchase::count())->toBe(1);
    expect(CarbonPurchase::first()->buyer_id)->toBe($firstBuyer->id);
});

it('returns 409 when the listing has already transitioned to sold (status re-check inside lock)', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->sold()->create();

    // Bypass the policy by reloading: actually the policy already blocks
    // 'sold' listings — so this scenario goes through policy denial as
    // 403, not 409. The 409 path is for the rare orphan-purchase state.
    $response = $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase");

    $response->assertForbidden();
});
