<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\InvalidStateTransition;
use Database\Factories\CarbonListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class CarbonListing extends Model
{
    /** @use HasFactory<CarbonListingFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RECALLED = 'recalled';
    public const STATUS_SOLD = 'sold';

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_RECALLED,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_SOLD,
            self::STATUS_RECALLED,
        ],
        self::STATUS_REJECTED => [],
        self::STATUS_RECALLED => [],
        self::STATUS_SOLD => [],
    ];

    /**
     * Model-level defaults so a freshly mass-assigned listing carries
     * `status = pending` in memory (matching the migration default).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'needs_workers' => false,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'hectares',
        'tonnes_co2e',
        'location',
        'price_twd',
        'status',
        'needs_workers',
        'admin_note',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'hectares' => 'decimal:2',
            'tonnes_co2e' => 'decimal:2',
            'price_twd' => 'decimal:2',
            'needs_workers' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchase(): HasOne
    {
        return $this->hasOne(CarbonPurchase::class);
    }

    /**
     * Transition this listing to a new status via the allowed-transition map.
     * Caller MUST call save() afterwards. On `approved`, stamps approved_by +
     * approved_at. On `rejected`, stores $extras['admin_note'] if present.
     *
     * @param  array<string, mixed>  $extras
     *
     * @throws InvalidStateTransition
     */
    public function transitionTo(string $newStatus, array $extras = []): void
    {
        self::assertValidTransition($this->status, $newStatus);

        $this->status = $newStatus;

        if ($newStatus === self::STATUS_APPROVED) {
            $this->approved_by = Auth::id();
            $this->approved_at = now();
        }

        if ($newStatus === self::STATUS_REJECTED && array_key_exists('admin_note', $extras)) {
            $this->admin_note = $extras['admin_note'];
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
        static::saving(function (CarbonListing $listing): void {
            // Skip the check on creation. Only existing rows whose status
            // is being modified are subject to the allowed-transition rule.
            if (! $listing->exists || ! $listing->isDirty('status')) {
                return;
            }

            $original = (string) $listing->getOriginal('status');
            $new = (string) $listing->status;

            self::assertValidTransition($original, $new);
        });

        static::saved(function (CarbonListing $listing): void {
            // Sold + needs_workers → atomically open a maintenance WorkerJob.
            // Any QueryException (e.g. stale UNIQUE collision) bubbles up and
            // rolls the surrounding purchase transaction back.
            if (! $listing->wasChanged('status')) {
                return;
            }

            if ($listing->status !== self::STATUS_SOLD) {
                return;
            }

            if (! $listing->needs_workers) {
                return;
            }

            WorkerJob::create([
                'carbon_listing_id' => $listing->id,
                'status' => WorkerJob::STATUS_OPEN,
            ]);
        });
    }
}
