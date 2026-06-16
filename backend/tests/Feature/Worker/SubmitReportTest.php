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

function makeClaimedJob(): array
{
    $worker = User::factory()->create();
    WorkerApplication::factory()->for($worker)->approved()->create();
    $job = WorkerJob::factory()->claimed($worker)->create();

    return [$worker, $job];
}

it('happy path: submits a report with two valid JPEGs', function () {
    [$worker, $job] = makeClaimedJob();

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => '已完成整地與初步清理',
                'before_image' => UploadedFile::fake()->image('before.jpg', 1024, 768),
                'after_image' => UploadedFile::fake()->image('after.jpg', 1024, 768),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertCreated()
        ->assertJsonPath('report.status', 'pending');

    $report = WorkerJobReport::where('worker_job_id', $job->id)->first();
    expect($report)->not->toBeNull();
    expect($report->worker_id)->toBe($worker->id);

    Storage::disk('public')->assertExists($report->before_image_path);
    Storage::disk('public')->assertExists($report->after_image_path);

    expect($job->fresh()->status)->toBe('reported');
});

it('rejects oversized image with 422', function () {
    [$worker, $job] = makeClaimedJob();

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => 'x',
                'before_image' => UploadedFile::fake()->image('big.jpg')->size(6 * 1024), // 6 MB
                'after_image' => UploadedFile::fake()->image('after.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertStatus(422)->assertJsonValidationErrors(['before_image']);
    expect(WorkerJobReport::count())->toBe(0);
});

it('rejects non-image upload with 422', function () {
    [$worker, $job] = makeClaimedJob();

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => 'x',
                'before_image' => UploadedFile::fake()->create('evil.php', 100, 'application/x-php'),
                'after_image' => UploadedFile::fake()->image('after.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertStatus(422)->assertJsonValidationErrors(['before_image']);
});

it('rejects datetime_end <= datetime_start with 422', function () {
    [$worker, $job] = makeClaimedJob();

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->toDateTimeString(),
                'datetime_end' => now()->subHour()->toDateTimeString(),
                'content' => 'x',
                'before_image' => UploadedFile::fake()->image('b.jpg'),
                'after_image' => UploadedFile::fake()->image('a.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertStatus(422)->assertJsonValidationErrors(['datetime_end']);
});

it('non-claiming worker cannot submit a report — 403', function () {
    [$claimer, $job] = makeClaimedJob();

    $intruder = User::factory()->create();
    WorkerApplication::factory()->for($intruder)->approved()->create();

    $response = $this->actingAs($intruder)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => 'x',
                'before_image' => UploadedFile::fake()->image('b.jpg'),
                'after_image' => UploadedFile::fake()->image('a.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertForbidden();
});

it('double-submit returns 409 via UNIQUE constraint', function () {
    [$worker, $job] = makeClaimedJob();

    WorkerJobReport::factory()->create([
        'worker_job_id' => $job->id,
        'worker_id' => $worker->id,
    ]);
    // The pending report already moved the job to reported; the policy denies
    // since job is no longer 'claimed'. So the response is 403, not 409.
    $job->transitionTo(WorkerJob::STATUS_REPORTED);
    $job->save();

    $response = $this->actingAs($worker)
        ->post(
            "/api/worker-jobs/{$job->id}/report",
            [
                'datetime_start' => now()->subHours(4)->toDateTimeString(),
                'datetime_end' => now()->subHours(1)->toDateTimeString(),
                'content' => 'x',
                'before_image' => UploadedFile::fake()->image('b.jpg'),
                'after_image' => UploadedFile::fake()->image('a.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

    $response->assertForbidden();
});
