<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarbonPurchase>
 */
class CarbonPurchaseFactory extends Factory
{
    protected $model = CarbonPurchase::class;

    public function definition(): array
    {
        return [
            'carbon_listing_id' => CarbonListing::factory()->sold(),
            'buyer_id' => User::factory(),
            'price_twd' => fake()->randomFloat(2, 10_000, 1_000_000),
        ];
    }
}
