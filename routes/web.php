<?php

use App\Http\Controllers\Admin\AiSettingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CredentialController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\SearchSettingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoredTopicController;
use App\Http\Controllers\NewsItemController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PushTestController;
use App\Http\Controllers\SearchHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, '__invoke'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'readonly'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationSettingController::class, 'edit'])->name('notifications.edit');
    Route::put('/notifications', [NotificationSettingController::class, 'update'])->name('notifications.update');
    Route::patch('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');

    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscriptions.store');
    Route::delete('/push/subscriptions/{pushSubscription}', [PushSubscriptionController::class, 'destroy'])->name('push.subscriptions.destroy');

    Route::get('/push-test', [PushTestController::class, 'show'])->name('push.test');
    Route::post('/push-test', [PushTestController::class, 'send'])->name('push.test.send');

    Route::resource('topics', MonitoredTopicController::class);
    Route::patch('/topics/{topic}/pause', [MonitoredTopicController::class, 'pause'])->name('topics.pause');
    Route::patch('/topics/{topic}/resume', [MonitoredTopicController::class, 'resume'])->name('topics.resume');
    Route::post('/topics/{topic}/run', [MonitoredTopicController::class, 'run'])->name('topics.run');

    Route::get('/novedades', [NewsItemController::class, 'index'])->name('news-items.index');
    Route::get('/novedades/{newsItem}', [NewsItemController::class, 'show'])->name('news-items.show');
    Route::post('/news-items/{newsItem}/read', [NewsItemController::class, 'read'])->name('news-items.read');
    Route::post('/news-items/{newsItem}/save', [NewsItemController::class, 'save'])->name('news-items.save');
    Route::post('/news-items/{newsItem}/irrelevant', [NewsItemController::class, 'irrelevant'])->name('news-items.irrelevant');
    Route::patch('/news-items/{newsItem}/read', [NewsItemController::class, 'read'])->name('news-items.read.patch');
    Route::patch('/news-items/{newsItem}/save', [NewsItemController::class, 'save'])->name('news-items.save.patch');
    Route::patch('/news-items/{newsItem}/irrelevant', [NewsItemController::class, 'irrelevant'])->name('news-items.irrelevant.patch');

    Route::get('/historial-busquedas', [SearchHistoryController::class, 'index'])->name('search-history.index');
});

Route::middleware(['auth', 'verified', 'admin', 'readonly'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->name('users.reactivate');

        Route::get('/topics', [AdminTopicController::class, 'index'])->name('topics.index');
        Route::post('/topics/bulk', [AdminTopicController::class, 'bulk'])->name('topics.bulk');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/reset', [SettingController::class, 'reset'])->name('settings.reset');

        Route::get('/mail', [MailSettingController::class, 'edit'])->name('mail.settings');
        Route::put('/mail', [MailSettingController::class, 'update'])->name('mail.settings.update');
        Route::post('/mail/test', [MailSettingController::class, 'test'])->name('mail.test');

        Route::get('/search', [SearchSettingController::class, 'edit'])->name('search.settings');
        Route::put('/search', [SearchSettingController::class, 'update'])->name('search.settings.update');

        Route::get('/ai', [AiSettingController::class, 'edit'])->name('ai.settings');
        Route::put('/ai', [AiSettingController::class, 'update'])->name('ai.settings.update');

        Route::get('/credentials/{scope}/{provider}/{name}', [CredentialController::class, 'confirm'])
            ->name('credentials.confirm');
        Route::post('/credentials/{scope}/{provider}/{name}', [CredentialController::class, 'destroy'])
            ->name('credentials.destroy');

        Route::get('/monitoring/runs', [MonitoringController::class, 'runs'])->name('monitoring.runs');
        Route::get('/monitoring/deliveries', [MonitoringController::class, 'deliveries'])->name('monitoring.deliveries');
        Route::get('/monitoring/errors', [MonitoringController::class, 'errors'])->name('monitoring.errors');

        Route::get('/audit', AuditLogController::class)->name('audit');
    });

require __DIR__.'/auth.php';
