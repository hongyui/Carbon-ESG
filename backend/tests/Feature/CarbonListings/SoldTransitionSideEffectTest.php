<?php

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use App\Models\WorkerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-creates an open WorkerJob when a needs_workers listing is purchased', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()
        ->approved()
        ->create(['needs_workers' => true]);

    $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase")
        ->assertCreated();

    expect($listing->fresh()->status)->toBe('sold');

    $job = WorkerJob::where('carbon_listing_id', $listing->id)->first();
    expect($job)->not->toBeNull();
    expect($job->status)->toBe('open');
    expect($job->worker_id)->toBeNull();
});

it('does NOT create a WorkerJob when needs_workers is false', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()
        ->approved()
        ->create(['needs_workers' => false]);

    $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase")
        ->assertCreated();

    expect($listing->fresh()->status)->toBe('sold');
    expect(WorkerJob::where('carbon_listing_id', $listing->id)->exists())->toBeFalse();
});

it('rolls back the purchase if WorkerJob auto-create fails on UNIQUE collision', function () {
    $buyer = User::factory()->create();
    $listing = CarbonListing::factory()
        ->approved()
        ->create(['needs_workers' => true]);

    // Pre-seed a stale WorkerJob row for this listing so the auto-create
    // hits the UNIQUE(carbon_listing_id) constraint.
    WorkerJob::create([
        'carbon_listing_id' => $listing->id,
        'status' => WorkerJob::STATUS_OPEN,
    ]);

    $response = $this->actingAs($buyer)
        ->postJson("/api/carbon-listings/{$listing->id}/purchase");

    // The purchase transaction must have rolled back entirely.
    expect($listing->fresh()->status)->toBe('approved');
    expect(CarbonPurchase::where('carbon_listing_id', $listing->id)->exists())->toBeFalse();

    // The controller catches QueryException 23000 and returns 409 Conflict.
    $response->assertStatus(409);
});
