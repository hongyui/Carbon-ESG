<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('owner can view their own pending listing', function () {
    $user = User::factory()->create();
    $pending = CarbonListing::factory()->for($user)->create();

    $response = $this->actingAs($user)->getJson("/api/carbon-listings/{$pending->id}");

    $response->assertOk()->assertJsonPath('listing.id', $pending->id);
});

it('non-owner gets 403 on a non-approved listing', function () {
    $owner = User::factory()->create();
    $pending = CarbonListing::factory()->for($owner)->create();
    $other = User::factory()->create();

    $response = $this->actingAs($other)->getJson("/api/carbon-listings/{$pending->id}");

    $response->assertForbidden();
});

it('non-owner can view an approved listing', function () {
    $owner = User::factory()->create();
    $approved = CarbonListing::factory()->approved()->for($owner)->create();
    $other = User::factory()->create();

    $response = $this->actingAs($other)->getJson("/api/carbon-listings/{$approved->id}");

    $response->assertOk()->assertJsonPath('listing.id', $approved->id);
});

it('admin can view any listing regardless of status', function () {
    $owner = User::factory()->create();
    $rejected = CarbonListing::factory()->rejected()->for($owner)->create();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson("/api/carbon-listings/{$rejected->id}");

    $response->assertOk();
});

it('returns 404 for non-existent listing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/carbon-listings/999999');

    $response->assertNotFound();
});
