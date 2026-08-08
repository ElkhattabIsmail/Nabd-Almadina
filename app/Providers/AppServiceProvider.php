<?php

namespace App\Providers;

use App\Models\Departement;
use App\Models\Incident;
use App\Models\Signalement;
use App\Policies\DepartementPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\SignalementPolicy;
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
        Gate::policy(Signalement::class, SignalementPolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(Departement::class, DepartementPolicy::class);
    }
}
