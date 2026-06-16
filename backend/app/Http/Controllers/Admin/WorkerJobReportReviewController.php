<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidStateTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\RejectRequest;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkerJobReportReviewController extends Controller
{
    public function pending(): JsonResponse
    {
        Gate::authorize('viewAdminQueue', WorkerJobReport::class);

        return response()->json(
            WorkerJobReport::query()
                ->where('status', WorkerJobReport::STATUS_PENDING)
                ->with(['workerJob.carbonListing', 'worker:id,name,email'])
                ->oldest()
                ->paginate(12)
        );
    }

    public function approve(WorkerJobReport $workerJobReport): JsonResponse
    {
        Gate::authorize('approve', $workerJobReport);

        try {
            DB::transaction(function () use ($workerJobReport) {
                $workerJobReport->transitionTo(WorkerJobReport::STATUS_APPROVED);
                $workerJobReport->save();

                $job = $workerJobReport->workerJob;
                $job->transitionTo(WorkerJob::STATUS_APPROVED);
                $job->save();
            });
        } catch (InvalidStateTransition $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'report' => $workerJobReport->fresh(),
        ]);
    }

    public function reject(RejectRequest $request, WorkerJobReport $workerJobReport): JsonResponse
    {
        Gate::authorize('reject', $workerJobReport);

        try {
            DB::transaction(function () use ($request, $workerJobReport) {
                // The saved listener on WorkerJobReport bounces the parent job
                // back to claimed when status transitions to rejected.
                $workerJobReport->transitionTo(
                    WorkerJobReport::STATUS_REJECTED,
                    ['review_reason' => $request->validated('reason')],
                );
                $workerJobReport->save();
            });
        } catch (InvalidStateTransition $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'report' => $workerJobReport->fresh(),
        ]);
    }
}
