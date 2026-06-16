<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidStateTransition;
use Database\Factories\WorkerJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class WorkerJob extends Model
{
    /** @use HasFactory<WorkerJobFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_APPROVED = 'approved';

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_OPEN => [self::STATUS_CLAIMED],
        self::STATUS_CLAIMED => [self::STATUS_REPORTED],
        // reported → claimed is the rejection-bounce path triggered by the
        // WorkerJobReport.saved listener when admin rejects a report.
        self::STATUS_REPORTED => [self::STATUS_APPROVED, self::STATUS_CLAIMED],
        self::STATUS_APPROVED => [],
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    protected $fillable = [
        'carbon_listing_id',
        'worker_id',
        'status',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }

    public function carbonListing(): BelongsTo
    {
        return $this->belongsTo(CarbonListing::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(WorkerJobReport::class);
    }

    /**
     * @param  array<string, mixed>  $extras
     *
     * @throws InvalidStateTransition
     */
    public function transitionTo(string $newStatus, array $extras = []): void
    {
        self::assertValidTransition($this->status, $newStatus);

        $previous = $this->status;
        $this->status = $newStatus;

        if ($newStatus === self::STATUS_CLAIMED) {
            // First-time claim (from open) sets the worker and stamps claimed_at.
            // Re-claim path (from rejected) keeps the original worker and timestamp.
            if ($previous === self::STATUS_OPEN) {
                $this->worker_id = $extras['worker_id'] ?? Auth::id();
                $this->claimed_at = now();
            }
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
        static::saving(function (WorkerJob $job): void {
            if (! $job->exists || ! $job->isDirty('status')) {
                return;
            }

            $original = (string) $job->getOriginal('status');
            $new = (string) $job->status;

            self::assertValidTransition($original, $new);
        });
    }
}
