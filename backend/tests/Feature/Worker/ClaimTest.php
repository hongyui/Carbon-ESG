<?php

use App\Models\User;
use App\Models\WorkerApplication;
use App\Models\WorkerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('happy path: approved worker claims an open job', function () {
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();
    $job = WorkerJob::factory()->create();

    $response = $this->actingAs($worker)
        ->postJson("/api/worker-jobs/{$job->id}/claim");

    $response->assertOk()
        ->assertJsonPath('job.status', 'claimed')
        ->assertJsonPath('job.worker_id', $worker->id);

    expect($job->fresh()->claimed_at)->not->toBeNull();
});

it('non-worker cannot claim — 403', function () {
    $user = User::factory()->create();
    $job = WorkerJob::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/worker-jobs/{$job->id}/claim");

    $response->assertForbidden();
    expect($job->fresh()->status)->toBe('open');
    expect($job->fresh()->worker_id)->toBeNull();
});

it('claiming an already-claimed job returns 403 (policy denies non-open)', function () {
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();
    $job = WorkerJob::factory()->claimed()->create();

    $response = $this->actingAs($worker)
        ->postJson("/api/worker-jobs/{$job->id}/claim");

    $response->assertForbidden();
});

// NOTE: the in-transaction post-lock check (`if ($locked->status !== open) return 409`)
// is structural concurrency armor — exercised by real two-process contention,
// not by single-process Pest. Verified by source review:
// app/Http/Controllers/WorkerJobController.php — claim() method.

it('rejects anonymous claim with 401', function () {
    $job = WorkerJob::factory()->create();
    $response = $this->postJson("/api/worker-jobs/{$job->id}/claim");
    $response->assertUnauthorized();
});
