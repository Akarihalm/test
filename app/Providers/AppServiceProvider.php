<?php

namespace App\Providers;

use App\Utils\SlackManager;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * @package App\Providers
 */

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (config('telescope.enabled') && ! $this->app->isProduction()) {
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->singleton('slack', SlackManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ResourceCollection::withoutWrapping();
    }
}
