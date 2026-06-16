<?php

use App\Models\User;
use App\Models\WorkerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('isWorker returns false when the user has no application', function () {
    $user = User::factory()->create();

    expect($user->isWorker())->toBeFalse();
});

it('isWorker returns false when the application is pending', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->create();

    expect($user->isWorker())->toBeFalse();
});

it('isWorker returns false when the application is rejected', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->rejected()->create();

    expect($user->isWorker())->toBeFalse();
});

it('isWorker returns true once the application is approved', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->approved()->create();

    expect($user->isWorker())->toBeTrue();
});

it('isWorker uses an indexed EXISTS subquery rather than loading the row', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->approved()->create();

    DB::enableQueryLog();
    $user->isWorker();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toHaveCount(1);
    expect(strtolower($queries[0]['query']))->toContain('exists');
});
