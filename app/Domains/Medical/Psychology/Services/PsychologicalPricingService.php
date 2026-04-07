<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Services;

use App\Domains\Medical\Psychology\Models\PsychologicalBooking;
use App\Domains\Medical\Psychology\Models\PsychologicalService;

/**
 * Сервис ценообразования для психологических консультаций.
 *
 * Рассчитывает финальную стоимость с учётом:
 * - базовой цены услуги;
 * - скидки для первого визита;
 * - пакетных скидок (количество сессий).
 *
 * Цены хранятся в копейках (int).  Никаких float для денег.
 *
 * @see PsychologicalService  модель услуги
 * @see PsychologicalBooking  модель бронирования
 * @package App\Domains\Medical\Psychology\Services
 */
final readonly class PsychologicalPricingService
{
    /** Скидка (%) для первого визита клиента. */
    private const FIRST_VISIT_DISCOUNT_PCT = 10;

    /** Минимальное количество сессий для пакетной скидки. */
    private const PACKAGE_THRESHOLD = 5;

    /** Скидка (%) при покупке пакета сессий. */
    private const PACKAGE_DISCOUNT_PCT = 15;

    /**
     * Рассчитать финальную стоимость услуги для клиента.
     *
     * @param  int  $serviceId  Идентификатор психологической услуги.
     * @param  int  $clientId   Идентификатор клиента.
     * @return int  Финальная цена в копейках.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если услуга не найдена.
     */
    public function calculateFinalPrice(int $serviceId, int $clientId): int
    {
        /** @var PsychologicalService $service */
        $service = PsychologicalService::findOrFail($serviceId);
        $basePrice = (int) $service->price;

        $discount = $this->resolveDiscountPercent($clientId);

        if ($discount > 0) {
            return (int) round($basePrice * (1 - $discount / 100));
        }

        return $basePrice;
    }

    /**
     * Определить применимый процент скидки для клиента.
     *
     * Приоритет: пакетная скидка (если >= 5 визитов) → скидка первого визита → 0.
     *
     * @param  int  $clientId  Идентификатор клиента.
     * @return int  Процент скидки (0–100).
     */
    private function resolveDiscountPercent(int $clientId): int
    {
        $totalVisits = PsychologicalBooking::where('client_id', $clientId)->count();

        if ($totalVisits >= self::PACKAGE_THRESHOLD) {
            return self::PACKAGE_DISCOUNT_PCT;
        }

        if ($totalVisits === 0) {
            return self::FIRST_VISIT_DISCOUNT_PCT;
        }

        return 0;
    }

    /**
     * Рассчитать стоимость пакета сессий.
     *
     * @param  int  $serviceId      Идентификатор услуги.
     * @param  int  $sessionsCount  Количество сессий в пакете.
     * @return int  Итоговая стоимость пакета в копейках.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если услуга не найдена.
     * @throws \InvalidArgumentException Если количество сессий ≤ 0.
     */
    public function calculatePackagePrice(int $serviceId, int $sessionsCount): int
    {
        if ($sessionsCount <= 0) {
            throw new \InvalidArgumentException('Sessions count must be positive.');
        }

        /** @var PsychologicalService $service */
        $service = PsychologicalService::findOrFail($serviceId);
        $unitPrice = (int) $service->price;

        $totalBeforeDiscount = $unitPrice * $sessionsCount;

        if ($sessionsCount >= self::PACKAGE_THRESHOLD) {
            return (int) round($totalBeforeDiscount * (1 - self::PACKAGE_DISCOUNT_PCT / 100));
        }

        return $totalBeforeDiscount;
    }
}
