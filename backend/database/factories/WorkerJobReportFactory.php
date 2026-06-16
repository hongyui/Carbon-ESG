<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerJobReport>
 */
class WorkerJobReportFactory extends Factory
{
    protected $model = WorkerJobReport::class;

    public function definition(): array
    {
        return [
            'worker_job_id' => WorkerJob::factory()->reported(),
            'worker_id' => User::factory(),
            'datetime_start' => now()->subHours(6),
            'datetime_end' => now()->subHours(2),
            'before_image_path' => 'job-reports/'.fake()->md5().'.jpg',
            'after_image_path' => 'job-reports/'.fake()->md5().'.jpg',
            'content' => fake()->paragraph(),
            'status' => WorkerJobReport::STATUS_PENDING,
            'reviewer_id' => null,
            'review_reason' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => WorkerJobReport::STATUS_APPROVED,
            'reviewer_id' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Photos do not show actual maintenance'): static
    {
        return $this->state(fn () => [
            'status' => WorkerJobReport::STATUS_REJECTED,
            'reviewer_id' => User::factory()->admin(),
            'review_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
