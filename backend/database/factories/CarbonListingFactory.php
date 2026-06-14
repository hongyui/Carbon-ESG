<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarbonListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarbonListing>
 */
class CarbonListingFactory extends Factory
{
    protected $model = CarbonListing::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'hectares' => fake()->randomFloat(2, 0.5, 100),
            'tonnes_co2e' => fake()->randomFloat(2, 1, 500),
            'location' => fake()->city(),
            'price_twd' => fake()->randomFloat(2, 10_000, 1_000_000),
            'status' => CarbonListing::STATUS_PENDING,
            'admin_note' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => CarbonListing::STATUS_APPROVED,
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(string $note = 'Reject reason'): static
    {
        return $this->state(fn () => [
            'status' => CarbonListing::STATUS_REJECTED,
            'admin_note' => $note,
        ]);
    }

    public function recalled(): static
    {
        return $this->state(fn () => [
            'status' => CarbonListing::STATUS_RECALLED,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => CarbonListing::STATUS_SOLD,
            'approved_by' => User::factory()->admin(),
            'approved_at' => now()->subDay(),
        ]);
    }
}
