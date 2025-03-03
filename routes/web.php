<?php

use App\Http\Controllers\WebPushController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\EfiPayWebhookController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WebhookUrlController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::post('/webpush/subscribe', [WebPushController::class, 'subscribe'])->name('webpush.subscribe');
Route::post('/notifications/efipay/_listener', [EfiPayWebhookController::class, 'handle'])->middleware('cors')->name('efipay.webhook');

//Rotas públicas
Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::post('/create-new', [PublicController::class, 'createNewUrl'])->name('public.create-new-url');

Route::get('/view/{url_slug}', [PublicController::class, 'view'])->name('public.view');
Route::get('/{url_slug}/webhook/load', [PublicController::class, 'load'])->name('public.load');
Route::delete('/webhook/{id}', [PublicController::class, 'delete'])->name('public.delete');
Route::delete('/{url_slug}/webhook/delete-all', [PublicController::class, 'deleteAll'])->name('public.delete-all');
Route::get('/webhook/{id}', [PublicController::class, 'loadSingle'])->name('public.load-single');
Route::post('/webhook/{id}/retransmit', [PublicController::class, 'retransmitWebhook'])->name('public.retransmit');
Route::patch('/webhook/{id}/retransmitted', [PublicController::class, 'markRetransmitted'])->name(
    'public.mark-retransmitted'
);
Route::patch('/webhook/{id}/viewed', [PublicController::class, 'markAsViewed'])->name('public.mark-viewed');
Route::get('/webhook/{id}/notification-status', [PublicController::class, 'getNotificationStatus'])->name(
    'public.get-notification-status'
);
Route::patch('/webhook/{id}/toggle-notifications', [PublicController::class, 'toggleNotifications'])->name(
    'public.toggle-notifications'
);
Route::prefix('webhook-retransmission')->group(function () {
    // Rota para listar todas as URLs de retransmissão
    Route::get('urls', [WebhookUrlController::class, 'listRetransmissionUrls'])
        ->name('public.retransmission.list');

    // Rota para adicionar uma nova URL de retransmissão
    Route::post('urls', [WebhookUrlController::class, 'addRetransmissionUrl'])
        ->name('public.retransmission.add');

    // Rota para remover uma URL de retransmissão
    Route::delete('urls/{id}', [WebhookUrlController::class, 'removeRetransmissionUrl'])
        ->name('public.retransmission.remove');

    // Rota para listar URLs de retransmissão associadas a uma URL específica
    Route::get('urls/{url_id}', [WebhookUrlController::class, 'listRetransmissionUrlsForUrl'])
        ->name('public.retransmission.list-for-url');
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
    Route::delete('/subscription/{subscription_id}', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::get('/urls', [PublicController::class, 'listUrls'])->name('account.list-urls');
    Route::patch('/urls/{id}/update-slug', [PublicController::class, 'updateSlug'])->name('account.url.update-slug');
    Route::post('/create-url', [PublicController::class, 'createNewUrl'])->name('account.public.create');
    Route::get('/view/{url_slug}', [PublicController::class, 'view'])->name('account.public.view');
})->middleware('auth');

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('plans', PlanController::class);
    Route::get('plans/{plan}/sync', [PlanController::class, 'sync'])->name('plans.sync');
});

Route::any('/{slug}', [PublicController::class, 'listener'])->name('public.listener');
