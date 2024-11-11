<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::get('/', [WebhookController::class, 'createUrl'])->name('webhook.create-url');
Route::post('/webhook/create-new-url', [WebhookController::class, 'createNewUrl'])->name('webhook.create-new-url');
Route::any('/{url_hash}', [WebhookController::class, 'listener'])->name('webhook.listener');
Route::get('/view/{url_hash}', [WebhookController::class, 'view'])->name('webhook.view');
Route::get('/webhook/load/{url_hash}', [WebhookController::class, 'load'])->name('webhook.load');
Route::delete('/webhook/{uuid}', [WebhookController::class, 'delete'])->name('webhook.delete');
Route::delete('/webhook/delete-all/{url_hash}', [WebhookController::class, 'deleteAll'])->name('webhook.delete-all');
Route::get('/webhook/{id}', [WebhookController::class, 'loadSingle'])->name('webhook.load-single');
Route::patch('/webhook/{id}/retransmit', [WebhookController::class, 'markRetransmitted'])->name('webhook.mark-retransmitted');
Route::patch('/webhook/{id}/viewed', [WebhookController::class, 'markAsViewed'])->name('webhook.mark-viewed');
