<?php

namespace Weboldalnet\FlipCity;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Weboldalnet\FlipCity\Models\FlipCitySettings;
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

        $this->publishes([
            __DIR__."/../resources/assets/css" => public_path("packages/flip-city/css"),
        ], "flip-city-css");

        $this->publishes([
            __DIR__."/../resources/assets/js" => public_path("packages/flip-city/js"),
        ], "flip-city-js");

        $this->publishes([
            __DIR__."/../resources/assets" => public_path("packages/flip-city"),
        ], "flip-city-assets");

        // View Composer a $flipCitySettings változóhoz
        View::composer(['flip-city::admin.*', 'flip-city::site.*'], function ($view) {
            $flipCitySettings = [
                'default_rate' => FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500)),
                'companion_price' => FlipCitySettings::get('companion_price', config('flip-city.companion_price', 500)),
                'profile_qr_print_text' => FlipCitySettings::get('profile_qr_print_text', config('flip-city.profile_qr_print_text', 'Kérjük, mutassa be ezt a kódot a belépéshez!')),
                'show_profile_booking' => FlipCitySettings::get('show_profile_booking', config('flip-city.show_profile_booking', true)),
                'billing_enabled' => config('flip-city.billing_enabled', true),
                'invoice_enabled' => config('flip-city.invoice_enabled', true),
                'assets_published' => file_exists(public_path('packages/flip-city')),
                'css_published' => file_exists(public_path('packages/flip-city/css')),
                'js_published' => file_exists(public_path('packages/flip-city/js')),
            ];
            $view->with('flipCitySettings', $flipCitySettings);
        });

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