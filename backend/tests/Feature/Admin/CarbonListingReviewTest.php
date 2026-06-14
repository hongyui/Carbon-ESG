<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the pending queue scoped to pending listings only, oldest first', function () {
    $admin = User::factory()->admin()->create();

    $olderPending = CarbonListing::factory()->create(['created_at' => now()->subDays(2)]);
    $newerPending = CarbonListing::factory()->create(['created_at' => now()]);
    CarbonListing::factory()->approved()->create();
    CarbonListing::factory()->rejected()->create();
    CarbonListing::factory()->recalled()->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/carbon-listings/pending');

    $response->assertOk()->assertJsonCount(2, 'data');

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$olderPending->id, $newerPending->id]);
});

it('non-admin gets 403 on the pending queue', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/admin/carbon-listings/pending');

    $response->assertForbidden();
});

it('admin can approve a pending listing and stamps approved_by + approved_at', function () {
    $admin = User::factory()->admin()->create();
    $listing = CarbonListing::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/carbon-listings/{$listing->id}/approve");

    $response->assertOk()->assertJsonPath('listing.status', 'approved');

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe('approved');
    expect((int) $fresh->approved_by)->toBe($admin->id);
    expect($fresh->approved_at)->not->toBeNull();
});

it('admin can reject a pending listing with an optional reason', function () {
    $admin = User::factory()->admin()->create();
    $listing = CarbonListing::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/carbon-listings/{$listing->id}/reject", [
            'reason' => '面積數據與地籍資料不符',
        ]);

    $response->assertOk()->assertJsonPath('listing.status', 'rejected');

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->admin_note)->toBe('面積數據與地籍資料不符');
});

it('non-admin gets 403 on approve and reject', function () {
    $user = User::factory()->create();
    $listing = CarbonListing::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/admin/carbon-listings/{$listing->id}/approve")
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson("/api/admin/carbon-listings/{$listing->id}/reject")
        ->assertForbidden();

    expect($listing->fresh()->status)->toBe('pending');
});

it('admin cannot approve a non-pending listing (policy denies)', function () {
    $admin = User::factory()->admin()->create();
    $approved = CarbonListing::factory()->approved()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/carbon-listings/{$approved->id}/approve");

    $response->assertForbidden();
});
