<?php

use Illuminate\Support\Facades\Route;


Route::namespace('Weboldalnet\FlipCity\Http\Controllers\Site')->domain(getSiteDomain())->middleware('web', 'site_share')->group(function () {
    Route::get('/register', 'RegistrationController@showRegistrationForm')->name('flip-city.register.show');
    Route::post('/register', 'RegistrationController@register')->name('flip-city.register');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', 'ProfileController@index')->name('flip-city.profile');
        Route::post('/booking', 'BookingController@store')->name('flip-city.booking.store');
    });
});

Route::namespace('Weboldalnet\FlipCity\Http\Controllers\Admin')->domain(getAdminDomain())->middleware('web', 'admin_share')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        Route::namespace('FlipCity')->group(function () {
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