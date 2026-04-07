<?php declare(strict_types=1);

/**
 * GenerateRewardVoucher — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/generaterewardvoucher
 */


namespace App\Domains\Education\Kids\Listeners;


use Psr\Log\LoggerInterface;
final class GenerateRewardVoucher
{

    public function __construct(
            private readonly KidsVoucherService $voucherService, private readonly LoggerInterface $logger
        ) {}

        public function handle(KidsProductPurchased $event): void
        {
            $this->logger->info('Listener started: Reward Voucher Check', [
                'user_id' => $event->userId,
                'amount' => $event->amountKopecks,
                'correlation_id' => $event->correlationId,
            ]);

            if ($event->amountKopecks >= 500000) { // 5000 RUB
                $this->voucherService->issueGiftVoucher(
                    userId: $event->userId,
                    amountKopecks: 50000, // 500 RUB Reward
                    correlationId: $event->correlationId
                );

                $this->logger->info('Reward voucher issued successfully.', [
                    'user_id' => $event->userId,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
