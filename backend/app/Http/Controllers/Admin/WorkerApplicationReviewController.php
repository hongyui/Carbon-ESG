<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidStateTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\RejectRequest;
use App\Models\WorkerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkerApplicationReviewController extends Controller
{
    public function pending(): JsonResponse
    {
        Gate::authorize('viewAdminQueue', WorkerApplication::class);

        return response()->json(
            WorkerApplication::where('status', WorkerApplication::STATUS_PENDING)
                ->with('user:id,name,email')
                ->oldest()
                ->paginate(12)
        );
    }

    public function approve(WorkerApplication $workerApplication): JsonResponse
    {
        Gate::authorize('approve', $workerApplication);

        try {
            DB::transaction(function () use ($workerApplication) {
                $workerApplication->transitionTo(WorkerApplication::STATUS_APPROVED);
                $workerApplication->save();
            });
        } catch (InvalidStateTransition $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'application' => $workerApplication->fresh(),
        ]);
    }

    public function reject(RejectRequest $request, WorkerApplication $workerApplication): JsonResponse
    {
        Gate::authorize('reject', $workerApplication);

        try {
            DB::transaction(function () use ($request, $workerApplication) {
                $workerApplication->transitionTo(
                    WorkerApplication::STATUS_REJECTED,
                    ['review_reason' => $request->validated('reason')],
                );
                $workerApplication->save();
            });
        } catch (InvalidStateTransition $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'application' => $workerApplication->fresh(),
        ]);
    }
}
