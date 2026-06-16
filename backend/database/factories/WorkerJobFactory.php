<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarbonListing;
use App\Models\User;
use App\Models\WorkerJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerJob>
 */
class WorkerJobFactory extends Factory
{
    protected $model = WorkerJob::class;

    public function definition(): array
    {
        return [
            'carbon_listing_id' => CarbonListing::factory()->sold()->state(['needs_workers' => true]),
            'worker_id' => null,
            'status' => WorkerJob::STATUS_OPEN,
            'claimed_at' => null,
        ];
    }

    public function claimed(?User $worker = null): static
    {
        return $this->state(fn () => [
            'status' => WorkerJob::STATUS_CLAIMED,
            'worker_id' => $worker?->id ?? User::factory(),
            'claimed_at' => now(),
        ]);
    }

    public function reported(?User $worker = null): static
    {
        return $this->state(fn () => [
            'status' => WorkerJob::STATUS_REPORTED,
            'worker_id' => $worker?->id ?? User::factory(),
            'claimed_at' => now()->subDay(),
        ]);
    }

    public function approved(?User $worker = null): static
    {
        return $this->state(fn () => [
            'status' => WorkerJob::STATUS_APPROVED,
            'worker_id' => $worker?->id ?? User::factory(),
            'claimed_at' => now()->subDays(2),
        ]);
    }
}
