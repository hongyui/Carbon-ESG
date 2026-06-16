<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Worker\CreateApplicationRequest;
use App\Models\WorkerApplication;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WorkerApplicationController extends Controller
{
    public function store(CreateApplicationRequest $request): JsonResponse
    {
        Gate::authorize('create', WorkerApplication::class);

        try {
            $application = WorkerApplication::create(array_merge(
                $request->validated(),
                ['user_id' => $request->user()->id],
            ));
        } catch (QueryException $e) {
            // UNIQUE(user_id) race fallback — same user POSTed twice between
            // the policy check and the insert.
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    'message' => '您已經提交過申請,請查看申請狀態。',
                ], Response::HTTP_CONFLICT);
            }

            throw $e;
        }

        return response()->json([
            'application' => $application,
        ], Response::HTTP_CREATED);
    }

    public function mine(Request $request): JsonResponse
    {
        $application = $request->user()->workerApplication()->first();

        if ($application === null) {
            return response()->json([
                'message' => '尚未提交工人申請。',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'application' => $application,
        ]);
    }
}
