<?php
$f = "app/Http/Controllers/Internal/PaymentWebhookController.php";
$c = file_get_contents($f);

// Tinkoff CONFIRMED logic
$c = preg_replace(
    '~// If captured \(CONFIRMED\) — credit wallet.*?// If failed~s',
    '// If captured (CONFIRMED) — credit wallet
                if ([\'Status\'] === \'CONFIRMED\' && ->status !== \'captured\') {
                    if (->hold_amount > 0) {
                        app(\App\Services\Wallet\WalletService::class)->releaseHold((int)->tenant_id, (int)->hold_amount, \'Payment captured\', );
                    }
                    app(\App\Services\Wallet\WalletService::class)->credit(
                        tenantId: (int)->tenant_id,
                        amount: (int)->amount,
                        type: \'deposit\',
                        sourceId: (string)->id,
                        correlationId: ,
                        reason: \'Payment captured from Tinkoff\',
                        sourceType: \'payment\'
                    );
                }

                // If failed',
    $c
);

// Tinkoff REJECTED
$c = preg_replace(
    '~// If failed — release hold.*?// Record in idempotency~s',
    '// If failed — release hold
                if ([\'Status\'] === \'REJECTED\' && ->hold_amount > 0) {
                    app(\App\Services\Wallet\WalletService::class)->releaseHold((int)->tenant_id, (int)->hold_amount, \'Payment rejected\', );
                }

                // Record in idempotency',
    $c
);

// Sberbank
$c = preg_replace(
    '~// Credit to wallet.*?(?=// Record in idempotency|// Update payment)~s',
    '// Credit to wallet
                if ([\'status\'] == 1 && ->status !== \'captured\') {
                    if (->hold_amount > 0) {
                        app(\App\Services\Wallet\WalletService::class)->releaseHold((int)->tenant_id, (int)->hold_amount, \'Payment captured\', );
                    }
                    app(\App\Services\Wallet\WalletService::class)->credit(
                        tenantId: (int)->tenant_id,
                        amount: (int)->amount,
                        type: \'deposit\',
                        sourceId: (string)->id,
                        correlationId: ,
                        reason: \'Payment captured from Sber\',
                        sourceType: \'payment\'
                    );
                }

                // Release hold
                if ([\'status\'] == 0 && ->hold_amount > 0) {
                    app(\App\Services\Wallet\WalletService::class)->releaseHold((int)->tenant_id, (int)->hold_amount, \'Payment rejected\', );
                }

                ',
    $c
);

file_put_contents($f, $c);
echo "Webhook controller partially updated.";
