<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payments\Presentation\Http\Controllers\WebhookController;

Route::prefix('webhook')->name('webhook.')->group(function (): void {
    Route::post('/tinkoff', [WebhookController::class, 'tinkoff'])->name('tinkoff');
});
