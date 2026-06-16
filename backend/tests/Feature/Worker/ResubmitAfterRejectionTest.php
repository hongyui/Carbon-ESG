<?php

use App\Models\User;
use App\Models\WorkerApplication;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('after admin rejects a report, the same worker can submit a fresh one', function () {
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();
    $job = WorkerJob::factory()->reported($worker)->create();

    $rejected = WorkerJobReport::factory()->rejected()->create([
        'worker_job_id' => $job->id,
        'worker_id' => $worker->id,
    ]);

    // Rejection happened — the saved listener should have flipped the job
    // back to claimed. (In production this happens inside the admin reject
    // controller; the factory bypasses that, so we manually reset for the
    // fixture.)
    $job->transitionTo(WorkerJob::STATUS_CLAIMED);
    $job->save();

    $oldRejectedId = $rejected->id;

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => '已重新整地與補充照片',
                'before_image' => UploadedFile::fake()->image('before2.jpg'),
                'after_image' => UploadedFile::fake()->image('after2.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertCreated();

    // Old rejected row was deleted as part of the resubmission transaction.
    expect(WorkerJobReport::find($oldRejectedId))->toBeNull();

    // Exactly one report row exists for this job, and it is pending.
    $reports = WorkerJobReport::where('worker_job_id', $job->id)->get();
    expect($reports)->toHaveCount(1);
    expect($reports->first()->status)->toBe('pending');

    expect($job->fresh()->status)->toBe('reported');
});
