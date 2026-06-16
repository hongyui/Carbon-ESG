<?php

use App\Models\CarbonListing;
use App\Models\User;
use App\Models\WorkerApplication;
use App\Models\WorkerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns open jobs to an approved worker', function () {
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();

    $job = WorkerJob::factory()->create();

    $response = $this->actingAs($worker)->getJson('/api/worker-jobs/open');

    $response->assertOk();
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($job->id);
});

it('excludes the calling worker\'s own land from the queue', function () {
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();

    // Listing owned by the worker — their own land
    $ownListing = CarbonListing::factory()->sold()->for($worker)->create(['needs_workers' => true]);
    WorkerJob::factory()->create(['carbon_listing_id' => $ownListing->id]);

    // Listing owned by someone else
    $otherListing = CarbonListing::factory()->sold()->create(['needs_workers' => true]);
    $otherJob = WorkerJob::factory()->create(['carbon_listing_id' => $otherListing->id]);

    $response = $this->actingAs($worker)->getJson('/api/worker-jobs/open');

    $response->assertOk();
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.id'))->toBe($otherJob->id);
});

it('non-worker gets 403 on the open queue', function () {
    $user = User::factory()->create();
    WorkerJob::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/worker-jobs/open');

    $response->assertForbidden();
});

it('rejects anonymous access with 401', function () {
    $response = $this->getJson('/api/worker-jobs/open');
    $response->assertUnauthorized();
});
