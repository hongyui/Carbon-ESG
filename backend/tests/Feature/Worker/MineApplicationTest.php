<?php

use App\Models\User;
use App\Models\WorkerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 when the user has no application', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/worker-applications/mine');

    $response->assertStatus(404);
});

it('returns the application when one exists', function () {
    $user = User::factory()->create();
    $application = WorkerApplication::factory()->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/worker-applications/mine');

    $response->assertOk()
        ->assertJsonPath('application.id', $application->id)
        ->assertJsonPath('application.status', 'pending');
});

it('returns the application reflecting the approved state', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->approved()->create();

    $response = $this->actingAs($user)->getJson('/api/worker-applications/mine');

    $response->assertOk()->assertJsonPath('application.status', 'approved');
});
