<?php

use Illuminate\Support\Facades\Route;

Route::get('/register', 'RegistrationController@showRegistrationForm')->name('flip-city.register.show');
Route::post('/register', 'RegistrationController@register')->name('flip-city.register');

Route::middleware('auth')->group(function () {
    Route::get('/profile', 'ProfileController@index')->name('flip-city.profile');
    Route::post('/booking', 'BookingController@store')->name('flip-city.booking.store');
});
