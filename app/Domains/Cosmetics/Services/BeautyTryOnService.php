<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

final class BeautyTryOnService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    public function logTryOn(int $productId, int $userId, bool $purchased, string $correlationId): bool
    {




        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($productId, $userId, $purchased, $correlationId) {
                $this->db->table('cosmetic_tryons')->insert([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'purchased' => $purchased,
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                $this->log->channel('audit')->info('Cosmetic try-on logged', [
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'purchased' => $purchased,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Try-on logging failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
