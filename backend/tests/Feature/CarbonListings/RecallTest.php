<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('owner can recall a pending listing', function () {
    $user = User::factory()->create();
    $listing = CarbonListing::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertOk()->assertJsonPath('listing.status', 'recalled');
    expect($listing->fresh()->status)->toBe('recalled');
});

it('owner can recall an approved listing', function () {
    $user = User::factory()->create();
    $listing = CarbonListing::factory()->approved()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertOk();
    expect($listing->fresh()->status)->toBe('recalled');
});

it('owner cannot recall a sold listing (policy denies)', function () {
    $user = User::factory()->create();
    $listing = CarbonListing::factory()->sold()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertForbidden();
    expect($listing->fresh()->status)->toBe('sold');
});

it('owner cannot recall a rejected listing (policy denies)', function () {
    $user = User::factory()->create();
    $listing = CarbonListing::factory()->rejected()->for($user)->create();

    $response = $this->actingAs($user)
        ->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertForbidden();
    expect($listing->fresh()->status)->toBe('rejected');
});

it('non-owner cannot recall', function () {
    $owner = User::factory()->create();
    $listing = CarbonListing::factory()->for($owner)->create();
    $other = User::factory()->create();

    $response = $this->actingAs($other)
        ->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertForbidden();
    expect($listing->fresh()->status)->toBe('pending');
});

it('rejects anonymous recall with 401', function () {
    $listing = CarbonListing::factory()->create();

    $response = $this->postJson("/api/carbon-listings/{$listing->id}/recall");

    $response->assertUnauthorized();
});
