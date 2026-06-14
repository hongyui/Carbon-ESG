<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CarbonListing;
use App\Models\User;

class CarbonListingPolicy
{
    public function view(User $user, CarbonListing $listing): bool
    {
        return $listing->status === CarbonListing::STATUS_APPROVED
            || $user->id === $listing->user_id
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function recall(User $user, CarbonListing $listing): bool
    {
        return $user->id === $listing->user_id
            && in_array(
                $listing->status,
                [CarbonListing::STATUS_PENDING, CarbonListing::STATUS_APPROVED],
                true,
            );
    }

    public function purchase(User $user, CarbonListing $listing): bool
    {
        return $user->id !== $listing->user_id
            && $listing->status === CarbonListing::STATUS_APPROVED;
    }

    public function approve(User $user, CarbonListing $listing): bool
    {
        return $user->isAdmin()
            && $listing->status === CarbonListing::STATUS_PENDING;
    }

    public function reject(User $user, CarbonListing $listing): bool
    {
        return $user->isAdmin()
            && $listing->status === CarbonListing::STATUS_PENDING;
    }
}
