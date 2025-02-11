<?php

use App\Http\Controllers\WebPushController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\EfiPayWebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookUrlController;
use Illuminate\Support\Facades\Route;

Route::post('/notifications/efipay/_listener', [EfiPayWebhookController::class, 'handle'])->middleware('cors')->name('efipay.webhook');

// Home e Autenticação
Route::post('/webpush/subscribe', [WebPushController::class, 'subscribe'])->name('webpush.subscribe');
Route::get('/', [WebhookController::class, 'createUrl'])->name('webhook.create');
Route::post('/create-new', [WebhookController::class, 'createNewUrl'])->name('webhook.create-new-url');

// Webhooks Públicos
Route::get('/view/{url_hash}', [WebhookController::class, 'view'])->name('webhook.view');
Route::get('/{url_hash}/webhook/load', [WebhookController::class, 'load'])->name('webhook.load');
Route::delete('/webhook/{id}', [WebhookController::class, 'delete'])->name('webhook.delete');
Route::delete('/{url_hash}/webhook/delete-all', [WebhookController::class, 'deleteAll'])->name('webhook.delete-all');
Route::get('/webhook/{id}', [WebhookController::class, 'loadSingle'])->name('webhook.load-single');
Route::post('/webhook/{id}/retransmit', [WebhookController::class, 'retransmitWebhook'])->name('webhook.retransmit');
Route::patch('/webhook/{id}/retransmitted', [WebhookController::class, 'markRetransmitted'])->name(
    'webhook.mark-retransmitted'
);
Route::patch('/webhook/{id}/viewed', [WebhookController::class, 'markAsViewed'])->name('webhook.mark-viewed');
Route::get('/webhook/{id}/notification-status', [WebhookController::class, 'getNotificationStatus'])->name(
    'webhook.get-notification-status'
);
Route::patch('/webhook/{id}/toggle-notifications', [WebhookController::class, 'toggleNotifications'])->name(
    'webhook.toggle-notifications'
);
Route::prefix('webhook-retransmission')->group(function () {
    // Rota para listar todas as URLs de retransmissão
    Route::get('urls', [WebhookUrlController::class, 'listRetransmissionUrls'])
        ->name('webhook.retransmission.list');

    // Rota para adicionar uma nova URL de retransmissão
    Route::post('urls', [WebhookUrlController::class, 'addRetransmissionUrl'])
        ->name('webhook.retransmission.add');

    // Rota para remover uma URL de retransmissão
    Route::delete('urls/{id}', [WebhookUrlController::class, 'removeRetransmissionUrl'])
        ->name('webhook.retransmission.remove');

    // Rota para listar URLs de retransmissão associadas a uma URL específica
    Route::get('urls/{url_id}', [WebhookUrlController::class, 'listRetransmissionUrlsForUrl'])
        ->name('webhook.retransmission.list-for-url');
});


Route::prefix('account')->group(function () {
    Route::get('/register', [AccountController::class, 'showRegisterForm'])->name('form.register');
    Route::post('/register', [AccountController::class, 'register'])->name('register');
    Route::get('/login', [AccountController::class, 'showLoginForm'])->name('form.login');
    Route::post('/login', [AccountController::class, 'login'])->name('login');
});

Route::prefix('account')->group(function () {
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('/cancel-subscription', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::get('/urls', [WebhookController::class, 'listUrls'])->name('account.list-urls');
    Route::patch('/urls/{id}/update-slug', [WebhookController::class, 'updateSlug'])->name('account.url.update-slug');
    Route::post('/create-url', [WebhookController::class, 'createNewUrl'])->name('account.webhook.create');
    Route::get('/view/{url_hash}', [WebhookController::class, 'view'])->name('account.webhook.view');
})->middleware('auth');

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('plans', PlanController::class);
    Route::get('plans/{plan}/sync', [PlanController::class, 'sync'])->name('plans.sync');
});

Route::any('/{url_slug}/{url_hash}', [WebhookController::class, 'customListener'])->name('webhook.custom-listener');
Route::any('/{url_hash}', [WebhookController::class, 'listener'])->name('webhook.listener');
