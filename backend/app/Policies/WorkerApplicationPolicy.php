<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerApplication;

class WorkerApplicationPolicy
{
    public function create(User $user): bool
    {
        return ! $user->workerApplication()->exists();
    }

    public function view(User $user, WorkerApplication $application): bool
    {
        return $user->id === $application->user_id || $user->isAdmin();
    }

    public function viewAdminQueue(User $user): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, WorkerApplication $application): bool
    {
        return $user->isAdmin()
            && $application->status === WorkerApplication::STATUS_PENDING;
    }

    public function reject(User $user, WorkerApplication $application): bool
    {
        return $user->isAdmin()
            && $application->status === WorkerApplication::STATUS_PENDING;
    }
}
