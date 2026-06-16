<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidStateTransition;
use Database\Factories\WorkerJobReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class WorkerJobReport extends Model
{
    /** @use HasFactory<WorkerJobReportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ],
        self::STATUS_APPROVED => [],
        self::STATUS_REJECTED => [],
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $fillable = [
        'worker_job_id',
        'worker_id',
        'datetime_start',
        'datetime_end',
        'before_image_path',
        'after_image_path',
        'content',
        'status',
        'reviewer_id',
        'review_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'datetime_start' => 'datetime',
            'datetime_end' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function workerJob(): BelongsTo
    {
        return $this->belongsTo(WorkerJob::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @param  array<string, mixed>  $extras
     *
     * @throws InvalidStateTransition
     */
    public function transitionTo(string $newStatus, array $extras = []): void
    {
        self::assertValidTransition($this->status, $newStatus);

        $this->status = $newStatus;

        if (in_array($newStatus, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            $this->reviewer_id = Auth::id();
            $this->reviewed_at = now();
        }

        if ($newStatus === self::STATUS_REJECTED && array_key_exists('review_reason', $extras)) {
            $this->review_reason = $extras['review_reason'];
        }
    }

    /**
     * @throws InvalidStateTransition
     */
    private static function assertValidTransition(string $from, string $to): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? null;

        if ($allowed === null) {
            throw new InvalidStateTransition(
                "Unknown current status '{$from}'."
            );
        }

        if (! in_array($to, $allowed, true)) {
            throw new InvalidStateTransition(
                "Cannot transition from '{$from}' to '{$to}'."
            );
        }
    }

    protected static function booted(): void
    {
        static::saving(function (WorkerJobReport $report): void {
            if (! $report->exists || ! $report->isDirty('status')) {
                return;
            }

            $original = (string) $report->getOriginal('status');
            $new = (string) $report->status;

            self::assertValidTransition($original, $new);
        });

        static::saved(function (WorkerJobReport $report): void {
            // When this report is rejected, bounce the parent job back to 'claimed'
            // so the same worker can resubmit. The job retains its worker_id.
            if (! $report->wasChanged('status')) {
                return;
            }

            if ($report->status !== self::STATUS_REJECTED) {
                return;
            }

            $job = $report->workerJob;

            if ($job === null) {
                return;
            }

            if ($job->status === WorkerJob::STATUS_REPORTED) {
                $job->transitionTo(WorkerJob::STATUS_CLAIMED);
                $job->save();
            }
        });
    }
}
