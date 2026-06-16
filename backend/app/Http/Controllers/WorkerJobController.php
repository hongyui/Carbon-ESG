<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Worker\SubmitReportRequest;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkerJobController extends Controller
{
    public function openQueue(Request $request): JsonResponse
    {
        Gate::authorize('viewOpenQueue', WorkerJob::class);

        $jobs = WorkerJob::query()
            ->where('status', WorkerJob::STATUS_OPEN)
            ->whereHas(
                'carbonListing',
                fn ($q) => $q->where('user_id', '!=', $request->user()->id),
            )
            ->with('carbonListing')
            ->oldest()
            ->paginate(12);

        return response()->json($jobs);
    }

    public function mine(Request $request): JsonResponse
    {
        $jobs = WorkerJob::query()
            ->where('worker_id', $request->user()->id)
            ->with(['carbonListing', 'report'])
            ->latest('updated_at')
            ->paginate(50);

        return response()->json($jobs);
    }

    public function show(WorkerJob $workerJob): JsonResponse
    {
        Gate::authorize('view', $workerJob);

        $workerJob->load(['carbonListing', 'report']);

        return response()->json([
            'job' => $workerJob,
        ]);
    }

    public function claim(Request $request, WorkerJob $workerJob): JsonResponse
    {
        Gate::authorize('claim', $workerJob);

        $userId = $request->user()->id;

        try {
            $job = DB::transaction(function () use ($workerJob, $userId) {
                $locked = WorkerJob::lockForUpdate()->find($workerJob->id);

                if (! $locked || $locked->status !== WorkerJob::STATUS_OPEN) {
                    abort(Response::HTTP_CONFLICT, '這個工作機會已經被認領了。');
                }

                $locked->transitionTo(WorkerJob::STATUS_CLAIMED, ['worker_id' => $userId]);
                $locked->save();

                return $locked;
            });
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        }

        return response()->json([
            'job' => $job->fresh(),
        ]);
    }

    public function submitReport(SubmitReportRequest $request, WorkerJob $workerJob): JsonResponse
    {
        Gate::authorize('submitReport', $workerJob);

        $report = DB::transaction(function () use ($request, $workerJob) {
            // If a rejected report exists for this job, replace it. UNIQUE on
            // worker_job_id requires deletion before the new insert.
            WorkerJobReport::where('worker_job_id', $workerJob->id)
                ->where('status', WorkerJobReport::STATUS_REJECTED)
                ->delete();

            $beforePath = $request->file('before_image')->store('job-reports', 'public');
            $afterPath = $request->file('after_image')->store('job-reports', 'public');

            $report = WorkerJobReport::create([
                'worker_job_id' => $workerJob->id,
                'worker_id' => $request->user()->id,
                'datetime_start' => $request->validated('datetime_start'),
                'datetime_end' => $request->validated('datetime_end'),
                'before_image_path' => $beforePath,
                'after_image_path' => $afterPath,
                'content' => $request->validated('content'),
            ]);

            $workerJob->transitionTo(WorkerJob::STATUS_REPORTED);
            $workerJob->save();

            return $report;
        });

        return response()->json([
            'report' => $report,
        ], Response::HTTP_CREATED);
    }
}
