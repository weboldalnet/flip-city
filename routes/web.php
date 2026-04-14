<?php

use Illuminate\Support\Facades\Route;


Route::middleware('web')->group(function () {
    // Site routes
    Route::namespace('Weboldalnet\FlipCity\Http\Controllers\Site')->group(function () {
        Route::get('/register', 'RegistrationController@showRegistrationForm')->name('flip-city.register.show');
        Route::post('/register', 'RegistrationController@register')->name('flip-city.register');

        Route::middleware('auth')->group(function () {
            Route::get('/profile', 'ProfileController@index')->name('flip-city.profile');
            Route::post('/booking', 'BookingController@store')->name('flip-city.booking.store');
        });
    });

    // Admin routes with /flip-city prefix
    Route::prefix(config('flip-city.routes.admin_prefix', 'flip-city'))->namespace('Weboldalnet\FlipCity\Http\Controllers\Admin')->group(function () {
        Route::middleware(config('flip-city.routes.admin_middleware', ['web', 'auth:admin']))->group(function () {
            Route::get('/', 'DashboardController@index')->name('flip-city.admin.dashboard');
            Route::post('/close-day', 'DashboardController@closeDay')->name('flip-city.admin.close-day');

            Route::get('/entries', 'EntryController@index')->name('flip-city.admin.entries');
            Route::post('/entries/scan', 'EntryController@scan')->name('flip-city.admin.entries.scan');
            Route::post('/entries/{entry}/checkout', 'EntryController@checkout')->name('flip-city.admin.entries.checkout');

            Route::get('/invoices', 'InvoiceController@index')->name('flip-city.admin.invoices');
            Route::get('/invoices/{invoice}/print', 'InvoiceController@print')->name('flip-city.admin.invoices.print');
        });
    });
});