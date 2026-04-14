<?php

namespace Weboldalnet\FlipCity;

use Illuminate\Support\ServiceProvider;
use Weboldalnet\FlipCity\Support\PackageHelper;

class FlipCityServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__."/../config/flip-city.php", "flip-city");
        $this->loadRoutesFrom(__DIR__."/../routes/web.php");
        $this->loadViewsFrom(__DIR__."/../resources/views", "flip-city");
        $this->loadMigrationsFrom(__DIR__."/../database/migrations");

        $this->publishes([
            __DIR__."/../config/flip-city.php" => config_path("flip-city.php"),
        ], "flip-city-config");

//        $publishList = [];
//        foreach (PackageHelper::PACKAGE_LIST as $name => $publish) {
//            $this->publishes([
//                $publish["source"] => base_path($publish["destination"]),
//            ], "flip-city-" . $name);
//
//            $publishList[$publish["source"]] = base_path($publish["destination"]);
//        }
//
//        $this->publishes($publishList, "flip-city-all");

        // Automatikus publik�l�s, ha a c�l k�nyvt�r nem l�tezik
        if ($this->app->runningInConsole() && !file_exists(public_path("packages/flip-city"))) {
            $this->publishes([
                __DIR__."/../resources/assets" => public_path("packages/flip-city"),
            ], "flip-city-assets");
        }
    }

    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Weboldalnet\FlipCity\Console\Commands\AutoCloseEntries::class,
            ]);
        }
    }
}