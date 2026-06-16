<?php

use App\Models\User;
use App\Models\WorkerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin sees the pending queue (oldest first)', function () {
    $admin = User::factory()->admin()->create();
    $oldest = WorkerApplication::factory()->create(['created_at' => now()->subDay()]);
    $newest = WorkerApplication::factory()->create();
    WorkerApplication::factory()->approved()->create();

    $response = $this->actingAs($admin)->getJson('/api/admin/worker-applications/pending');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($oldest->id);
    expect($response->json('total'))->toBe(2);
});

it('non-admin gets 403 on pending queue', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/admin/worker-applications/pending');

    $response->assertForbidden();
});

it('admin approves a pending application', function () {
    $admin = User::factory()->admin()->create();
    $application = WorkerApplication::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/worker-applications/{$application->id}/approve");

    $response->assertOk()
        ->assertJsonPath('application.status', 'approved');

    $fresh = $application->fresh();
    expect($fresh->status)->toBe('approved');
    expect((int) $fresh->reviewer_id)->toBe($admin->id);
    expect($fresh->reviewed_at)->not->toBeNull();
});

it('admin rejects with reason stores review_reason', function () {
    $admin = User::factory()->admin()->create();
    $application = WorkerApplication::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/worker-applications/{$application->id}/reject", [
            'reason' => '聯絡方式無法驗證',
        ]);

    $response->assertOk()
        ->assertJsonPath('application.status', 'rejected')
        ->assertJsonPath('application.review_reason', '聯絡方式無法驗證');
});

it('non-admin cannot approve or reject', function () {
    $user = User::factory()->create();
    $application = WorkerApplication::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/admin/worker-applications/{$application->id}/approve")
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson("/api/admin/worker-applications/{$application->id}/reject")
        ->assertForbidden();

    expect($application->fresh()->status)->toBe('pending');
});

it('admin cannot double-approve an already approved application', function () {
    $admin = User::factory()->admin()->create();
    $application = WorkerApplication::factory()->approved()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/worker-applications/{$application->id}/approve");

    // Policy blocks at the front door: status is no longer pending.
    $response->assertForbidden();
});
