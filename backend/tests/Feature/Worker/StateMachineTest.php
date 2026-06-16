<?php

use App\Exceptions\InvalidStateTransition;
use App\Models\User;
use App\Models\WorkerApplication;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// -- WorkerApplication --

it('worker application: pending can transition to approved or rejected', function (string $to) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $app = WorkerApplication::factory()->create();
    expect(fn () => $app->transitionTo($to))->not->toThrow(InvalidStateTransition::class);
    expect($app->status)->toBe($to);
})->with(['approved', 'rejected']);

it('worker application: approved is terminal', function () {
    $app = WorkerApplication::factory()->approved()->create();
    expect(fn () => $app->transitionTo('rejected'))->toThrow(InvalidStateTransition::class);
    expect(fn () => $app->transitionTo('pending'))->toThrow(InvalidStateTransition::class);
});

it('worker application: rejected is terminal', function () {
    $app = WorkerApplication::factory()->rejected()->create();
    expect(fn () => $app->transitionTo('approved'))->toThrow(InvalidStateTransition::class);
});

it('worker application: approve stamps reviewer_id from Auth::id()', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $app = WorkerApplication::factory()->create();
    $app->transitionTo('approved');
    $app->save();

    $app->refresh();
    expect((int) $app->reviewer_id)->toBe($admin->id);
    expect($app->reviewed_at)->not->toBeNull();
});

it('worker application: reject stores review_reason when provided', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $app = WorkerApplication::factory()->create();
    $app->transitionTo('rejected', ['review_reason' => '年齡不符']);
    $app->save();

    expect($app->fresh()->review_reason)->toBe('年齡不符');
});

it('worker application: saving listener blocks invalid direct status assignment', function () {
    $app = WorkerApplication::factory()->approved()->create();
    expect(function () use ($app) {
        $app->status = 'pending';
        $app->save();
    })->toThrow(InvalidStateTransition::class);
});

// -- WorkerJob --

it('worker job: open can transition to claimed', function () {
    $worker = User::factory()->create();
    $this->actingAs($worker);

    $job = WorkerJob::factory()->create();
    expect(fn () => $job->transitionTo('claimed'))->not->toThrow(InvalidStateTransition::class);
    expect($job->status)->toBe('claimed');
    expect((int) $job->worker_id)->toBe($worker->id);
    expect($job->claimed_at)->not->toBeNull();
});

it('worker job: claimed can transition to reported', function () {
    $job = WorkerJob::factory()->claimed()->create();
    expect(fn () => $job->transitionTo('reported'))->not->toThrow(InvalidStateTransition::class);
});

it('worker job: reported can transition to approved (admin approves report)', function () {
    $job = WorkerJob::factory()->reported()->create();
    expect(fn () => $job->transitionTo('approved'))->not->toThrow(InvalidStateTransition::class);
});

it('worker job: reported can transition back to claimed (rejection bounce)', function () {
    $worker = User::factory()->create();
    $job = WorkerJob::factory()->reported()->create(['worker_id' => $worker->id]);

    expect(fn () => $job->transitionTo('claimed'))->not->toThrow(InvalidStateTransition::class);
    // worker_id is preserved through the bounce
    expect((int) $job->worker_id)->toBe($worker->id);
});

it('worker job: approved is terminal', function () {
    $job = WorkerJob::factory()->approved()->create();
    expect(fn () => $job->transitionTo('claimed'))->toThrow(InvalidStateTransition::class);
    expect(fn () => $job->transitionTo('open'))->toThrow(InvalidStateTransition::class);
});

it('worker job: open cannot skip to reported', function () {
    $job = WorkerJob::factory()->create();
    expect(fn () => $job->transitionTo('reported'))->toThrow(InvalidStateTransition::class);
});

it('worker job: saving listener blocks direct invalid status assignment', function () {
    $job = WorkerJob::factory()->approved()->create();
    expect(function () use ($job) {
        $job->status = 'open';
        $job->save();
    })->toThrow(InvalidStateTransition::class);
});

// -- WorkerJobReport --

it('worker job report: pending can transition to approved or rejected', function (string $to) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $report = WorkerJobReport::factory()->create();
    expect(fn () => $report->transitionTo($to))->not->toThrow(InvalidStateTransition::class);
})->with(['approved', 'rejected']);

it('worker job report: approved is terminal', function () {
    $report = WorkerJobReport::factory()->approved()->create();
    expect(fn () => $report->transitionTo('rejected'))->toThrow(InvalidStateTransition::class);
});

it('worker job report: reject side-effect flips parent job back to claimed', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $worker = User::factory()->create();
    $job = WorkerJob::factory()->reported()->create(['worker_id' => $worker->id]);
    $report = WorkerJobReport::factory()->create([
        'worker_job_id' => $job->id,
        'worker_id' => $worker->id,
    ]);

    $report->transitionTo('rejected', ['review_reason' => '照片模糊']);
    $report->save();

    expect($job->fresh()->status)->toBe('claimed');
    expect((int) $job->fresh()->worker_id)->toBe($worker->id);
    expect($report->fresh()->review_reason)->toBe('照片模糊');
});
