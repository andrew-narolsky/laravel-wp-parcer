<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LinkController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SiteController;
use Illuminate\Support\Facades\Route;

Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
]);

Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin');

    Route::get('notifications/poll', [NotificationController::class, 'poll'])->name('admin.notifications.poll');

    Route::post('sites/import', [SiteController::class, 'import'])->name('admin.sites.import');
    Route::post('sites/import-homepage', [SiteController::class, 'importHomepage'])->name('admin.sites.import_homepage');
    Route::resource('sites', SiteController::class)->names('admin.sites');
    Route::post('links/analyze', [LinkController::class, 'analyze'])->name('admin.links.analyze');
    Route::get('links/export', [LinkController::class, 'export'])->name('admin.links.export');
    Route::post('links/republish-posts', [LinkController::class, 'republishPosts'])->name('admin.links.republish_posts');
    Route::post('links/republish-homepage', [LinkController::class, 'republishHomepage'])->name('admin.links.republish_homepage');
    Route::post('links/remove-homepage-content', [LinkController::class, 'removeHomepageContent'])->name('admin.links.remove_homepage_content');
    Route::resource('links', LinkController::class)->names('admin.links');
    Route::post('links/{link}/publish', [LinkController::class, 'publish'])->name('admin.links.publish');
    Route::post('links/{link}/check', [LinkController::class, 'check'])->name('admin.links.check');
});
