<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerJob;

class WorkerJobPolicy
{
    public function viewOpenQueue(User $user): bool
    {
        return $user->isWorker();
    }

    public function viewMine(User $user): bool
    {
        return true; // any authenticated user can view their own jobs page (it just returns empty if none)
    }

    public function view(User $user, WorkerJob $job): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($job->worker_id !== null && $user->id === $job->worker_id) {
            return true;
        }

        return $user->isWorker() && $job->status === WorkerJob::STATUS_OPEN;
    }

    public function claim(User $user, WorkerJob $job): bool
    {
        return $user->isWorker() && $job->status === WorkerJob::STATUS_OPEN;
    }

    public function submitReport(User $user, WorkerJob $job): bool
    {
        return $user->id === $job->worker_id
            && $job->status === WorkerJob::STATUS_CLAIMED;
    }
}
