<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    // Site oldali route-ok
    Route::namespace('Weboldalnet\FlipCity\Http\Controllers\Site')
        ->domain(getSiteDomain())
        ->middleware('site_share')
        ->group(function () {
            // Auth flow
            Route::get('/flip-city/login', 'LoginController@showLoginForm')->name('flip-city.login.show');
            Route::post('/flip-city/login', 'LoginController@login')->name('flip-city.login');
            Route::get('/flip-city/logout', 'LoginController@logout')->name('flip-city.logout');

            Route::get('/flip-city/register', 'RegistrationController@showRegistrationForm')->name('flip-city.register.show');
            Route::post('/flip-city/register', 'RegistrationController@register')->name('flip-city.register');
            Route::get('/flip-city/activate/{token}', 'RegistrationController@activate')->name('flip-city.activate');

            Route::get('/flip-city/forgot-password', 'PasswordResetController@showForgotPasswordForm')->name('flip-city.password.request');
            Route::post('/flip-city/forgot-password', 'PasswordResetController@sendResetLinkEmail')->name('flip-city.password.email');
            Route::get('/flip-city/reset-password/{token}', 'PasswordResetController@showResetForm')->name('flip-city.password.reset');
            Route::get('/flip-city/setup-password/{token}', 'PasswordResetController@showSetupForm')->name('flip-city.password.setup');
            Route::post('/flip-city/reset-password', 'PasswordResetController@reset')->name('flip-city.password.update');

            Route::middleware('auth')->group(function () {
                Route::get('/flip-city/profile', 'ProfileController@index')->name('flip-city.profile');
                Route::get('/flip-city/entries', 'ProfileController@entries')->name('flip-city.entries.index');
                if (config('flip-city.profile_qr_print_enabled', true)) {
                    Route::get('/flip-city/profile/print-qr', 'ProfileController@printQR')->name('flip-city.profile.print-qr');
                }
                Route::post('/flip-city/booking', 'BookingController@store')->name('flip-city.booking.store');
                if (config('flip-city.billing_enabled', true)) {
                    Route::get('/flip-city/invoices/{invoice}/download', 'InvoiceController@download')->name('flip-city.invoices.download');
                }
            });
        });

    // Admin oldali route-ok
    Route::prefix(config('flip-city.routes.admin_prefix', 'flip-city'))
        ->namespace('Weboldalnet\FlipCity\Http\Controllers\Admin')
        ->domain(getAdminDomain())
        ->middleware('admin_share')
        ->group(function () {
            Route::middleware(config('flip-city.routes.admin_middleware', ['web', 'auth:admin']))->group(function () {

                // Dashboard
                Route::get('/', 'DashboardController@index')->name('flip-city.admin.dashboard');
                Route::post('/close-day', 'DashboardController@closeDay')->name('flip-city.admin.close-day');
                Route::post('/add-user', 'DashboardController@addUser')->name('flip-city.admin.add-user');

            // Belépések
            Route::get('/entries', 'EntryController@index')->name('flip-city.admin.entries');
            Route::post('/entries', 'EntryController@store')->name('flip-city.admin.entries.store');
            Route::post('/entries/store-manual', 'EntryController@storeManual')->name('flip-city.admin.entries.store-manual');
            Route::post('/entries/store-from-booking', 'EntryController@storeFromBooking')->name('flip-city.admin.entries.store-from-booking');
            Route::post('/entries/scan', 'EntryController@scan')->name('flip-city.admin.entries.scan');
            Route::post('/entries/{entry}/checkout', 'EntryController@checkout')->name('flip-city.admin.entries.checkout');
            Route::post('/entries/{entry}/finalize-checkout', 'EntryController@finalizeCheckout')->name('flip-city.admin.entries.finalize-checkout');
            Route::post('/entries/{entry}/partial-checkout', 'EntryController@partialCheckout')->name('flip-city.admin.entries.partial-checkout');
            Route::post('/entries/{entry}/fail', 'EntryController@fail')->name('flip-city.admin.entries.fail');
            Route::post('/entries/{entry}/unfail', 'EntryController@unfail')->name('flip-city.admin.entries.unfail');

            // Beállítások
            Route::get('/settings', 'DashboardController@settings')->name('flip-city.admin.settings');
            Route::post('/settings', 'DashboardController@updateSettings')->name('flip-city.admin.settings.update');

            // Számlák
            Route::post('/invoices', 'InvoiceController@store')->name('flip-city.admin.invoices.store');
            if (config('flip-city.billing_enabled', true)) {
                Route::get('/invoices', 'InvoiceController@index')->name('flip-city.admin.invoices');
                Route::get('/invoices/{invoice}/print', 'InvoiceController@print')->name('flip-city.admin.invoices.print');
                Route::get('/invoices/{invoice}/download', 'InvoiceController@download')->name('flip-city.admin.invoices.download');
            }

                // Felhasználók
                Route::get('/users', 'UserController@index')->name('flip-city.admin.users.index');
                Route::get('/users/{user}', 'UserController@show')->name('flip-city.admin.users.show');
                Route::get('/users/{user}/edit', 'UserController@edit')->name('flip-city.admin.users.edit');
                Route::put('/users/{user}', 'UserController@update')->name('flip-city.admin.users.update');
                Route::post('/users/{user}/toggle-active', 'UserController@toggleActive')->name('flip-city.admin.users.toggle-active');
                Route::post('/users/{user}/toggle-blocked', 'UserController@toggleBlocked')->name('flip-city.admin.users.toggle-blocked');
                Route::post('/users/{user}/send-password-reset', 'UserController@sendPasswordReset')->name('flip-city.admin.users.send-password-reset');
                Route::post('/users/{user}/generate-qr', 'UserController@generateQRCode')->name('flip-city.admin.users.generate-qr');
                Route::delete('/users/{user}', 'UserController@destroy')->name('flip-city.admin.users.destroy');
            });
        });
});
