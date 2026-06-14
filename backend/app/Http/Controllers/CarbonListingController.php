<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CarbonListings\CreateRequest;
use App\Models\CarbonListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CarbonListingController extends Controller
{
    public function store(CreateRequest $request): JsonResponse
    {
        $listing = CarbonListing::create(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id],
        ));

        return response()->json([
            'listing' => $listing,
        ], Response::HTTP_CREATED);
    }

    public function mine(Request $request): JsonResponse
    {
        $listings = $request->user()
            ->carbonListings()
            ->latest()
            ->get();

        return response()->json([
            'listings' => $listings,
        ]);
    }

    public function show(CarbonListing $carbonListing): JsonResponse
    {
        Gate::authorize('view', $carbonListing);

        return response()->json([
            'listing' => $carbonListing,
        ]);
    }

    public function recall(CarbonListing $carbonListing): JsonResponse
    {
        Gate::authorize('recall', $carbonListing);

        $carbonListing->transitionTo(CarbonListing::STATUS_RECALLED);
        $carbonListing->save();

        return response()->json([
            'listing' => $carbonListing->fresh(),
        ]);
    }
}
