<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CarbonPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarbonPurchase extends Model
{
    /** @use HasFactory<CarbonPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'carbon_listing_id',
        'buyer_id',
        'price_twd',
    ];

    protected function casts(): array
    {
        return [
            'price_twd' => 'decimal:2',
        ];
    }

    public function carbonListing(): BelongsTo
    {
        return $this->belongsTo(CarbonListing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
