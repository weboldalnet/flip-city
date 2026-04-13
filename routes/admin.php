<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'DashboardController@index')->name('flip-city.admin.dashboard');
Route::post('/close-day', 'DashboardController@closeDay')->name('flip-city.admin.close-day');

Route::get('/entries', 'EntryController@index')->name('flip-city.admin.entries');
Route::post('/entries/scan', 'EntryController@scan')->name('flip-city.admin.entries.scan');
Route::post('/entries/{entry}/checkout', 'EntryController@checkout')->name('flip-city.admin.entries.checkout');

Route::get('/invoices', 'InvoiceController@index')->name('flip-city.admin.invoices');
Route::get('/invoices/{invoice}/print', 'InvoiceController@print')->name('flip-city.admin.invoices.print');
