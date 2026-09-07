<?php

namespace App\Providers;

use App\Contracts\CurrentTenant;
use App\Contracts\SubscriptionAccess;
use App\Contracts\SubscriptionCheckout;
use App\Services\EloquentSubscriptionAccess;
use App\Services\ManualSubscriptionCheckout;
use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentTenant::class, TenantContext::class);
        $this->app->bind(SubscriptionAccess::class, EloquentSubscriptionAccess::class);
        $this->app->bind(SubscriptionCheckout::class, ManualSubscriptionCheckout::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
