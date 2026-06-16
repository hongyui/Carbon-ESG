<?php

namespace App\Providers;

use App\Models\CarbonListing;
use App\Models\WorkerApplication;
use App\Models\WorkerJob;
use App\Models\WorkerJobReport;
use App\Policies\CarbonListingPolicy;
use App\Policies\WorkerApplicationPolicy;
use App\Policies\WorkerJobPolicy;
use App\Policies\WorkerJobReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CarbonListing::class, CarbonListingPolicy::class);
        Gate::policy(WorkerApplication::class, WorkerApplicationPolicy::class);
        Gate::policy(WorkerJob::class, WorkerJobPolicy::class);
        Gate::policy(WorkerJobReport::class, WorkerJobReportPolicy::class);
    }
}
