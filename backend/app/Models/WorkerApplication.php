<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidStateTransition;
use Database\Factories\WorkerApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class WorkerApplication extends Model
{
    /** @use HasFactory<WorkerApplicationFactory> */
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
        'user_id',
        'reason',
        'has_experience',
        'age',
        'residence',
        'contact',
        'status',
        'reviewer_id',
        'review_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_experience' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        static::saving(function (WorkerApplication $application): void {
            if (! $application->exists || ! $application->isDirty('status')) {
                return;
            }

            $original = (string) $application->getOriginal('status');
            $new = (string) $application->status;

            self::assertValidTransition($original, $new);
        });
    }
}
