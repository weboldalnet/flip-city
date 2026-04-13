<?php

namespace Weboldalnet\FlipCity\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class FlipCityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/flip-city.php', 'flip-city'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'flip-city');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Weboldalnet\FlipCity\Console\Commands\AutoCloseEntries::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/flip-city.php' => config_path('flip-city.php'),
            ], 'flip-city-config');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/flip-city'),
            ], 'flip-city-views');

            $this->publishes([
                __DIR__ . '/../../resources/scss' => resource_path('scss/vendor/flip-city'),
                __DIR__ . '/../../resources/js' => resource_path('js/vendor/flip-city'),
            ], 'flip-city-assets');

            // Public assets (CSS/JS compiled files would go here in a real scenario)
            // But the request asks to put them in public/packages/flip-city
            $this->publishes([
                __DIR__ . '/../../resources/assets' => public_path('packages/flip-city'),
            ], 'flip-city-public');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });

        Route::group($this->adminRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
        });
    }

    /**
     * Get the standard route configuration.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('flip-city.routes.prefix'),
            'middleware' => config('flip-city.routes.middleware'),
            'namespace' => 'Weboldalnet\FlipCity\Http\Controllers\Site',
        ];
    }

    /**
     * Get the admin route configuration.
     */
    protected function adminRouteConfiguration(): array
    {
        return [
            'prefix' => config('flip-city.routes.admin_prefix'),
            'middleware' => config('flip-city.routes.admin_middleware'),
            'namespace' => 'Weboldalnet\FlipCity\Http\Controllers\Admin',
        ];
    }
}
