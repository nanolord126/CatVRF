<?php

declare(strict_types=1);

/**
 * B2BFlowerService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 */

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\B2BFlowerStorefront;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class B2BFlowerService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создать B2B-витрину для цветочного магазина.
     *
     * @param array  $data          Данные витрины
     * @param int    $userId        ID пользователя (для fraud-check)
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function createStorefront(array $data, int $userId, string $correlationId): B2BFlowerStorefront
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'b2b_flower_storefront_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): B2BFlowerStorefront {

                $storefront = B2BFlowerStorefront::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('B2B Flower storefront created', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

            return $storefront;
        });
    }

    /**
     * Идентификатор версии компонента.
     */
    private const VERSION = '1.0.0';

    /**
     * Максимальное количество повторных попыток.
     */
    private const MAX_RETRIES = 3;

    /**
     * TTL кэша по умолчанию (секунды).
     */
    private const CACHE_TTL = 3600;
}
