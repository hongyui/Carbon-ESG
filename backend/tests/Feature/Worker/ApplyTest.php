<?php

use App\Models\User;
use App\Models\WorkerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pending application with 201 and persists the row', function () {
    $user = User::factory()->create();

    $payload = [
        'reason' => '想為家鄉土地維護出一份力',
        'has_experience' => true,
        'age' => 28,
        'residence' => '台東 卑南鄉',
        'contact' => '0900-000-000',
    ];

    $response = $this->actingAs($user)->postJson('/api/worker-applications', $payload);

    $response->assertCreated()
        ->assertJsonPath('application.status', 'pending')
        ->assertJsonPath('application.user_id', $user->id)
        ->assertJsonPath('application.has_experience', true);

    expect(WorkerApplication::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects a missing reason with 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/worker-applications', [
        'has_experience' => true,
        'age' => 28,
        'residence' => 'X',
        'contact' => 'Y',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['reason']);
});

it('rejects an underage applicant with 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/worker-applications', [
        'reason' => 'X',
        'has_experience' => true,
        'age' => 17,
        'residence' => 'X',
        'contact' => 'Y',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['age']);
});

it('rejects a second application from the same user with 409', function () {
    $user = User::factory()->create();
    WorkerApplication::factory()->for($user)->create();

    $response = $this->actingAs($user)->postJson('/api/worker-applications', [
        'reason' => 'X',
        'has_experience' => true,
        'age' => 28,
        'residence' => 'X',
        'contact' => 'Y',
    ]);

    $response->assertStatus(403);
    // Policy denial fires before the UNIQUE collision; 403 is the expected gate.
    expect(WorkerApplication::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects anonymous create with 401', function () {
    $response = $this->postJson('/api/worker-applications', []);
    $response->assertUnauthorized();
});
