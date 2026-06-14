<?php

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pending listing with 201 and persists the row', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => '台東 鹿野 9.2 公頃',
        'description' => '紅葉部落公有林,樟樹楓香為主',
        'hectares' => 9.2,
        'tonnes_co2e' => 18.5,
        'location' => '台東 鹿野',
        'price_twd' => 250_000,
    ];

    $response = $this->actingAs($user)->postJson('/api/carbon-listings', $payload);

    $response->assertCreated()
        ->assertJsonPath('listing.status', 'pending')
        ->assertJsonPath('listing.title', '台東 鹿野 9.2 公頃')
        ->assertJsonPath('listing.user_id', $user->id);

    expect(CarbonListing::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects negative price with 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/carbon-listings', [
        'title' => 'Test',
        'description' => 'Test',
        'hectares' => 1.0,
        'tonnes_co2e' => 1.0,
        'location' => 'Test',
        'price_twd' => -100,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['price_twd']);
});

it('rejects missing title with 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/carbon-listings', [
        'description' => 'Test',
        'hectares' => 1.0,
        'tonnes_co2e' => 1.0,
        'location' => 'Test',
        'price_twd' => 1000,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['title']);
});

it('rejects anonymous create with 401', function () {
    $response = $this->postJson('/api/carbon-listings', []);

    $response->assertUnauthorized();
});
