<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
//        $this->app->register(\Robertbaelde\Saucy\SaucyServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
