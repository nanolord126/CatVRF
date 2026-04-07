<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Application\DTOs;

/**
 * Исключительно иммутабельный объект передачи данных (Data Transfer Object), представляющий
 * финальный результат расчета и применения скидки по выбранной промо-кампании.
 *
 * Категорически гарантирует консистентность передачи информации от ядра бизнес-логики
 * к слою контроллера или вызывающему Bounded Context без протечек доменных моделей.
 */
final readonly class DiscountResult
{
    /**
     * Конструктор, строго инициализирующий и абсолютно фиксирующий результат расчета скидки.
     *
     * @param bool $success Флаг, однозначно подтверждающий успешное применение промокода.
     * @param int $discountKopecks Абсолютная сумма успешно рассчитанной скидки в копейках.
     * @param string|null $errorMessage Человекочитаемое сообщение об ошибке, если $success равен false.
     */
    public function __construct(
        public bool $success,
        public int $discountKopecks,
        public ?string $errorMessage = null
    ) {
    }
}
