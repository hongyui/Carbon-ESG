<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerJobReport;

class WorkerJobReportPolicy
{
    public function viewAdminQueue(User $user): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, WorkerJobReport $report): bool
    {
        return $user->isAdmin()
            && $report->status === WorkerJobReport::STATUS_PENDING;
    }

    public function reject(User $user, WorkerJobReport $report): bool
    {
        return $user->isAdmin()
            && $report->status === WorkerJobReport::STATUS_PENDING;
    }
}
