<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class BeautyTryOnService
{
    public function __construct()
    {
    }

    public function logTryOn(int $productId, int $userId, bool $purchased, string $correlationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'logTryOn'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL logTryOn', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'logTryOn'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL logTryOn', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'logTryOn'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL logTryOn', ['domain' => __CLASS__]);

        try {
            DB::transaction(function () use ($productId, $userId, $purchased, $correlationId) {
                DB::table('cosmetic_tryons')->insert([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'purchased' => $purchased,
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                Log::channel('audit')->info('Cosmetic try-on logged', [
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'purchased' => $purchased,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Try-on logging failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
