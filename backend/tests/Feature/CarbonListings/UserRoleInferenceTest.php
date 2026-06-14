<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('isAdmin returns true for an admin user', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
});

it('isAdmin returns false for a general user', function () {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();
});

it('isSeller returns false when the user has no listings', function () {
    $user = User::factory()->create();

    expect($user->isSeller())->toBeFalse();
});

it('isSeller returns true after any listing exists for the user, even a recalled one', function () {
    $user = User::factory()->create();
    CarbonListing::factory()->recalled()->for($user)->create();

    expect($user->isSeller())->toBeTrue();
});

it('hasPurchased returns false when the user has no purchases', function () {
    $user = User::factory()->create();

    expect($user->hasPurchased())->toBeFalse();
});

it('hasPurchased returns true once a purchase row exists for the buyer', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()->sold()->create();
    CarbonPurchase::factory()->create([
        'carbon_listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
    ]);

    expect($buyer->hasPurchased())->toBeTrue();
});
