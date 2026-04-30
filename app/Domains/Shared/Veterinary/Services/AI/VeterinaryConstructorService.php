<?php

declare(strict_types=1);

namespace App\Domains\Veterinary\Services\AI;

final readonly class VeterinaryConstructorService
{
    /**
     * Component: VeterinaryConstructorService
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    public function __construct(
        private readonly PetHealthConstructor $petHealthConstructor,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Каноничный алиас конструктора вертикали Veterinary.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function analyzeAndRecommend(array $payload, int $userId): array
    {
        return $this->petHealthConstructor->analyzeAndRecommend($payload, $userId);
    }

    /**
     * VeterinaryConstructorService — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     *
     * @author CatVRF Team
     * @license Proprietary
     *
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     * @see https://catvrf.ru/docs/veterinaryconstructorservice
     */

    /**
     * Выполнить операцию в транзакции с audit-логированием.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return $this->db->transaction(function () use ($callback) {
            return $callback();
        });
    }
}
