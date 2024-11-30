<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookUrlController;
use Illuminate\Support\Facades\Route;

// Home e Autenticação
Route::get('/', [WebhookController::class, 'createUrl'])->name('webhook.create');

// Webhooks Públicos
Route::any('/{url_hash}', [WebhookController::class, 'listener'])->name('webhook.listener');
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


// Account
Route::prefix('account')->group(function () {
    Route::post('/register', [AccountController::class, 'register'])->name('account.register'); // Público
    Route::post('/login', [AccountController::class, 'login'])->name('account.login'); // Público

    // Apenas usuários autenticados podem acessar a rota de logout
    Route::post('/logout', [AccountController::class, 'logout'])
        ->middleware('auth')
        ->name('account.logout');
});

//// URLs e Webhooks vinculados a contas
Route::prefix('{account_slug}')->middleware('auth')->group(function () {
    Route::get('/{url_slug}', [WebhookController::class, 'listener'])->name('webhook.listener');
    Route::get('/view/{url_slug}', [WebhookController::class, 'view'])->name('webhook.view');
    Route::post('/create-url', [WebhookController::class, 'createNewUrl'])->name('webhook.create');
});
