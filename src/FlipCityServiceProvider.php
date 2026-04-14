<?php

namespace Weboldalnet\FlipCity;

use Illuminate\Support\ServiceProvider;
use Weboldalnet\FlipCity\Support\PackageHelper;

class FlipCityServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Konfiguráció
        $this->mergeConfigFrom(__DIR__.'/../config/flip-city.php', 'flip-city');

        // route-ok
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // View-k betöltése a csomag resources/views mappájából
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'flip-city');

        // Migrációk
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publikálás
        $this->publishes([
            __DIR__.'/../config/flip-city.php' => config_path('flip-city.php'),
        ], 'flip-city-config');

        $publishList = [];
        foreach (PackageHelper::PACKAGE_LIST as $name => $publish) {
            $this->publishes([
                $publish['source'] => base_path($publish['destination']),
            ], 'flip-city-' . $name);

            $publishList[$publish['source']] = base_path($publish['destination']);
        }

        $this->publishes($publishList, 'flip-city-all');
    }

    public function register()
    {
        // Regisztráljuk a Command-okat
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Weboldalnet\FlipCity\Console\Commands\AutoCloseEntries::class,
            ]);
        }
    }
}
