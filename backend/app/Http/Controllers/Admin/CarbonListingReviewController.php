<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarbonListings\RejectRequest;
use App\Models\CarbonListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CarbonListingReviewController extends Controller
{
    public function pending(): JsonResponse
    {
        Gate::authorize('viewAdminQueue', CarbonListing::class);

        return response()->json(
            CarbonListing::where('status', CarbonListing::STATUS_PENDING)
                ->oldest()
                ->paginate(12)
        );
    }

    public function approve(CarbonListing $carbonListing): JsonResponse
    {
        Gate::authorize('approve', $carbonListing);

        $carbonListing->transitionTo(CarbonListing::STATUS_APPROVED);
        $carbonListing->save();

        return response()->json([
            'listing' => $carbonListing->fresh(),
        ]);
    }

    public function reject(RejectRequest $request, CarbonListing $carbonListing): JsonResponse
    {
        Gate::authorize('reject', $carbonListing);

        $carbonListing->transitionTo(
            CarbonListing::STATUS_REJECTED,
            ['admin_note' => $request->validated('reason')],
        );
        $carbonListing->save();

        return response()->json([
            'listing' => $carbonListing->fresh(),
        ]);
    }
}
