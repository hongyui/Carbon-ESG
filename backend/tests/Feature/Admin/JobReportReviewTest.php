<?php

use App\Models\User;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin sees the pending report queue', function () {
    $admin = User::factory()->admin()->create();
    WorkerJobReport::factory()->create(); // pending
    WorkerJobReport::factory()->approved()->create();
    WorkerJobReport::factory()->rejected()->create();

    $response = $this->actingAs($admin)->getJson('/api/admin/job-reports/pending');

    $response->assertOk();
    expect($response->json('total'))->toBe(1);
});

it('non-admin gets 403 on the pending queue', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson('/api/admin/job-reports/pending');
    $response->assertForbidden();
});

it('admin approves a pending report and both report+job become approved', function () {
    $admin = User::factory()->admin()->create();
    $job = WorkerJob::factory()->reported()->create();
    $report = WorkerJobReport::factory()->create([
        'worker_job_id' => $job->id,
        'worker_id' => $job->worker_id,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/job-reports/{$report->id}/approve");

    $response->assertOk()
        ->assertJsonPath('report.status', 'approved');

    expect($job->fresh()->status)->toBe('approved');
});

it('admin rejects a pending report and the parent job bounces to claimed', function () {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create();
    $job = WorkerJob::factory()->reported($worker)->create();
    $report = WorkerJobReport::factory()->create([
        'worker_job_id' => $job->id,
        'worker_id' => $worker->id,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/job-reports/{$report->id}/reject", [
            'reason' => '前後照片明顯不是同一塊地',
        ]);

    $response->assertOk()
        ->assertJsonPath('report.status', 'rejected')
        ->assertJsonPath('report.review_reason', '前後照片明顯不是同一塊地');

    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe('claimed');
    expect((int) $freshJob->worker_id)->toBe($worker->id);
});

it('non-admin cannot approve or reject', function () {
    $user = User::factory()->create();
    $report = WorkerJobReport::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/admin/job-reports/{$report->id}/approve")
        ->assertForbidden();
    $this->actingAs($user)
        ->postJson("/api/admin/job-reports/{$report->id}/reject")
        ->assertForbidden();

    expect($report->fresh()->status)->toBe('pending');
});
