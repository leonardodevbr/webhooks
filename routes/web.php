<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\UrlController;
use App\Http\Controllers\WebhookController;

// Home e Autenticação
Route::get('/', [UrlController::class, 'createUrl'])->name('url.create-anonymous');

// Account
Route::prefix('account')->group(function () {
    Route::post('/register', [AccountController::class, 'register'])->name('account.register');
    Route::post('/login', [AccountController::class, 'login'])->name('account.login');
    Route::post('/logout', [AccountController::class, 'logout'])->name('account.logout');
});

// URLs e Webhooks vinculados a contas
Route::prefix('{account_slug}')->group(function () {
    Route::get('/{url_slug}', [UrlController::class, 'listener'])->name('url.listener');
    Route::get('/view/{url_slug}', [UrlController::class, 'view'])->name('url.view');
    Route::post('/create-url', [UrlController::class, 'createNewUrl'])->name('url.create');
});

// Webhooks (Públicos e Protegidos)
Route::prefix('webhook')->group(function () {
    Route::any('/{url_hash}', [WebhookController::class, 'listener'])->name('webhook.listener');
    Route::get('/view/{url_hash}', [WebhookController::class, 'view'])->name('webhook.view');
    Route::get('/load/{url_hash}', [WebhookController::class, 'load'])->name('webhook.load');
    Route::delete('/{uuid}', [WebhookController::class, 'delete'])->name('webhook.delete');
    Route::delete('/delete-all/{url_hash}', [WebhookController::class, 'deleteAll'])->name('webhook.delete-all');
    Route::get('/{id}', [WebhookController::class, 'loadSingle'])->name('webhook.load-single');
    Route::patch('/{id}/retransmit', [WebhookController::class, 'markRetransmitted'])->name('webhook.mark-retransmitted');
    Route::patch('/{id}/viewed', [WebhookController::class, 'markAsViewed'])->name('webhook.mark-viewed');
});
