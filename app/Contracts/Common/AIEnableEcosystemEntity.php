<?php

namespace App\Contracts\Common;

/**
 * Контракт для AI-активации сущности в экосистеме (Production 2026).
 * Обязателен для всех вертикалей: Taxi, Clinic, Hotel, Restaurant, Education, Events, Sports, Vet.
 *
 * Каждая вертикаль должна реализовать эти методы для интеграции с:
 * - Динамическим ценообразованием (AI-модели спроса, погода, гео)
 * - Фрод-детекцией и скорингом надежности
 * - Предиктивными чек-листами для операционного планирования
 *
 * @package App\Contracts\Common
 * @see \App\Services\AI\MLHelperService
 */
interface AIEnableEcosystemEntity
{
    /**
     * Получить динамически скорректированную цену от AI движка.
     *
     * Корректировка учитывает:
     * - Текущий спрос в гео-регионе (тепловые карты)
     * - Погодные условия (для доставки, такси, уличных сервисов)
     * - Сезонность и времени суток
     * - Историческую волатильность цены
     * - Конкуренцию в районе
     *
     * @param float $basePrice Базовая цена до корректировки
     * @param array<string, mixed> $context Контекст (location, time, weather, etc.)
     * @return float Скорректированная цена (может быть выше или ниже базовой, обычно ±20%)
     *
     * @example
     * $adjustedPrice = $service->getAiAdjustedPrice(100.0, [
     *     'location' => 'Moscow',
     *     'time' => '19:00',      // пиковая нагрузка
     *     'weather' => 'rain',    // увеличение спроса
     *     'day_of_week' => 'friday'
     * ]);
     * // Вернет ~130.0 (30% наценка в пиковый час)
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float;

    /**
     * Получить скор надежности сущности (Trust Score).
     *
     * Скор рассчитывается на основе:
     * - История операций (количество, объем, успешность)
     * - Отзывы и рейтинги от пользователей
     * - Возвраты, жалобы, чарджбеки
     * - Финансовая история (задолженности, просрочки)
     * - Поведенческие сигналы фрода (аномальные паттерны)
     *
     * @return int Скор от 0 до 100, где 100 - полностью надежная сущность
     *
     * @example
     * $trustScore = $seller->getTrustScore();
     * if ($trustScore < 30) {
     *     // Требуется 100% предоплата (высокий риск)
     *     $paymentMethod = 'prepaid';
     * } elseif ($trustScore < 70) {
     *     // Требуется 50% предоплата (средний риск)
     *     $paymentMethod = 'partial_prepaid';
     * } else {
     *     // Полный кредит (низкий риск)
     *     $paymentMethod = 'postpaid';
     * }
     */
    public function getTrustScore(): int;

    /**
     * Сгенерировать предиктивный чек-лист для операционного планирования.
     *
     * На основе исторических данных и ML моделей система генерирует:
     * - Чек-листы подготовки для мастеров/сотрудников
     * - Предупреждения о возможных проблемах
     * - Рекомендации по оптимизации процесса
     * - Прогнозы по материалам и ресурсам
     *
     * @return array<int, array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     priority: 'high'|'medium'|'low',
     *     category: string,
     *     estimated_time_minutes: int,
     *     resources_needed: string[],
     *     risk_probability: float
     * }> Массив предиктивных пунктов чек-листа
     *
     * @example
     * $checklist = $clinic->generateAiChecklist();
     * // Вернет:
     * [
     *     [
     *         'id' => 'prep_001',
     *         'title' => 'Проверка стерилизации инструментов',
     *         'priority' => 'high',
     *         'estimated_time_minutes' => 15,
     *         'risk_probability' => 0.05,
     *     ],
     *     [
     *         'id' => 'supply_warning',
     *         'title' => 'Внимание: заканчивается антисептик №3',
     *         'priority' => 'medium',
     *         'estimated_time_minutes' => 30,
     *         'risk_probability' => 0.8,
     *     ]
     * ]
     */
    public function generateAiChecklist(): array;
}
