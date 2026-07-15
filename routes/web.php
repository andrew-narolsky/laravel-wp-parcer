<?php

use App\Http\Controllers\Admin\BackupController;
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
    Route::post('links/remove-posts', [LinkController::class, 'removePosts'])->name('admin.links.remove_posts');
    Route::resource('links', LinkController::class)->names('admin.links');
    Route::post('links/{link}/publish', [LinkController::class, 'publish'])->name('admin.links.publish');
    Route::post('links/{link}/check', [LinkController::class, 'check'])->name('admin.links.check');

    Route::get('backups', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('admin.backups.store');
    Route::post('backups/upload', [BackupController::class, 'upload'])->name('admin.backups.upload');
    Route::get('backups/{filename}/download', [BackupController::class, 'download'])
        ->where('filename', '[\w\-\.]+')
        ->name('admin.backups.download');
    Route::post('backups/{filename}/restore', [BackupController::class, 'restore'])
        ->where('filename', '[\w\-\.]+')
        ->name('admin.backups.restore');
    Route::delete('backups/{filename}', [BackupController::class, 'destroy'])
        ->where('filename', '[\w\-\.]+')
        ->name('admin.backups.destroy');
});
