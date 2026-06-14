<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CarbonListings\CreateRequest;
use App\Models\CarbonListing;
use App\Models\CarbonPurchase;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CarbonListingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            CarbonListing::where('status', CarbonListing::STATUS_APPROVED)
                ->latest()
                ->paginate(12)
        );
    }

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

    public function purchase(Request $request, CarbonListing $carbonListing): JsonResponse
    {
        Gate::authorize('purchase', $carbonListing);

        try {
            $purchase = DB::transaction(function () use ($request, $carbonListing) {
                $locked = CarbonListing::lockForUpdate()->find($carbonListing->id);

                if (! $locked || $locked->status !== CarbonListing::STATUS_APPROVED) {
                    abort(Response::HTTP_CONFLICT, '這筆碳匯已經不再可購買。');
                }

                $purchase = CarbonPurchase::create([
                    'carbon_listing_id' => $locked->id,
                    'buyer_id' => $request->user()->id,
                    'price_twd' => $locked->price_twd,
                ]);

                $locked->transitionTo(CarbonListing::STATUS_SOLD);
                $locked->save();

                return $purchase;
            });

            return response()->json([
                'purchase' => $purchase,
            ], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            // UNIQUE constraint on carbon_purchases.carbon_listing_id —
            // theoretical concurrent-purchase fallback past the lockForUpdate.
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    'message' => '這筆碳匯已經被別人買走了。',
                ], Response::HTTP_CONFLICT);
            }

            throw $e;
        }
    }
}
