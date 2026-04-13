<?php

use App\Helpers\CustomPageHelper;

use App\Http\Controllers\Admin\Article\ArticleController;

Route::namespace('App\Http\Controllers\Site')->domain(getSiteDomain())->middleware('web', 'site_share')->group(function () {

});

Route::namespace('App\Http\Controllers\Admin')->domain(getAdminDomain())->middleware('web', 'admin_share')->group(function () {

    Route::middleware('auth:admin')->group(function () {
        Route::namespace('Article')->group(function () {

        });
    });
});
