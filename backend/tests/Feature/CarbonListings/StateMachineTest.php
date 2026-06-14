<?php

use App\Exceptions\InvalidStateTransition;
use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('pending can transition to approved, rejected, or recalled', function (string $to) {
    $listing = CarbonListing::factory()->create();

    expect(fn () => $listing->transitionTo($to))->not->toThrow(InvalidStateTransition::class);
    expect($listing->status)->toBe($to);
})->with(['approved', 'rejected', 'recalled']);

it('approved can transition to sold or recalled', function (string $to) {
    $listing = CarbonListing::factory()->approved()->create();

    expect(fn () => $listing->transitionTo($to))->not->toThrow(InvalidStateTransition::class);
    expect($listing->status)->toBe($to);
})->with(['sold', 'recalled']);

it('rejected is a terminal state', function () {
    $listing = CarbonListing::factory()->rejected()->create();

    expect(fn () => $listing->transitionTo('approved'))->toThrow(InvalidStateTransition::class);
    expect(fn () => $listing->transitionTo('sold'))->toThrow(InvalidStateTransition::class);
});

it('recalled is a terminal state', function () {
    $listing = CarbonListing::factory()->recalled()->create();

    expect(fn () => $listing->transitionTo('approved'))->toThrow(InvalidStateTransition::class);
});

it('sold is a terminal state', function () {
    $listing = CarbonListing::factory()->sold()->create();

    expect(fn () => $listing->transitionTo('recalled'))->toThrow(InvalidStateTransition::class);
});

it('approve stamps approved_by from Auth::id() and approved_at', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $listing = CarbonListing::factory()->create();
    $listing->transitionTo('approved');
    $listing->save();

    $listing->refresh();
    expect((int) $listing->approved_by)->toBe($admin->id);
    expect($listing->approved_at)->not->toBeNull();
});

it('reject stores admin_note when provided in extras', function () {
    $listing = CarbonListing::factory()->create();
    $listing->transitionTo('rejected', ['admin_note' => '面積數據與地籍資料不符']);
    $listing->save();

    expect($listing->fresh()->admin_note)->toBe('面積數據與地籍資料不符');
});

it('saving listener blocks direct status assignment to an invalid transition', function () {
    $listing = CarbonListing::factory()->sold()->create();

    expect(function () use ($listing) {
        $listing->status = 'pending';
        $listing->save();
    })->toThrow(InvalidStateTransition::class);
});

it('saving listener allows direct status assignment to a valid transition', function () {
    $listing = CarbonListing::factory()->create(); // pending

    $listing->status = 'approved';
    expect(fn () => $listing->save())->not->toThrow(InvalidStateTransition::class);
    expect($listing->fresh()->status)->toBe('approved');
});
