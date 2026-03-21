<?php

// routes/api.php — добавить в конце файла

// Payment Webhooks (Internal, no auth required but signature verified)
Route::prefix('internal/webhooks')->name('webhooks.')->group(function () {
    Route::post('payment/tinkoff', [PaymentWebhookController::class, 'tinkoffNotification'])
        ->name('payment.tinkoff');
    
    Route::post('payment/sber', [PaymentWebhookController::class, 'sberNotification'])
        ->name('payment.sber');
    
    Route::post('payment/tochka', [PaymentWebhookController::class, 'tochkaNotification'])
        ->name('payment.tochka');
});
