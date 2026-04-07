<?php

declare(strict_types=1);

/**
 * BeautyTryOnService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/beautytryonservice
 */


namespace App\Domains\Beauty\Cosmetics\Services;




use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BeautyTryOnService
{


    public function __construct(private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db, private LoggerInterface $logger, private Guard $guard)
        {
    }

        public function logTryOn(int $productId, int $userId, bool $purchased, string $correlationId): bool
        {

            try {
                            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($productId, $userId, $purchased, $correlationId) {
                    $this->db->table('cosmetic_tryons')->insert([
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'purchased' => $purchased,
                        'correlation_id' => $correlationId,
                        'created_at' => Carbon::now(),
                    ]);

                    $this->logger->info('Cosmetic try-on logged', [
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'purchased' => $purchased,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return true;
            } catch (\Throwable $e) {
                $this->logger->error('Try-on logging failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
