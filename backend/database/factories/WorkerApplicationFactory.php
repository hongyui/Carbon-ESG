<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerApplication>
 */
class WorkerApplicationFactory extends Factory
{
    protected $model = WorkerApplication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->paragraph(),
            'has_experience' => fake()->boolean(),
            'age' => fake()->numberBetween(18, 75),
            'residence' => fake()->city(),
            'contact' => fake()->phoneNumber(),
            'status' => WorkerApplication::STATUS_PENDING,
            'reviewer_id' => null,
            'review_reason' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => WorkerApplication::STATUS_APPROVED,
            'reviewer_id' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Not enough experience'): static
    {
        return $this->state(fn () => [
            'status' => WorkerApplication::STATUS_REJECTED,
            'reviewer_id' => User::factory()->admin(),
            'review_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
