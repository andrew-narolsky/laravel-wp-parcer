<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LinkController;
use App\Http\Controllers\Admin\SiteController;
use Illuminate\Support\Facades\Route;

Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
]);

Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin');

    Route::post('sites/import', [SiteController::class, 'import'])->name('admin.sites.import');
    Route::resource('sites', SiteController::class)->names('admin.sites');
    Route::resource('links', LinkController::class)->names('admin.links');
    Route::post('links/{link}/publish', [LinkController::class, 'publish'])->name('admin.links.publish');
});
