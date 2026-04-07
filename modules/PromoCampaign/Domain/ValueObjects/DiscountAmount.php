<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Строго определенный и абсолютно неизменяемый объект значения, представляющий сумму скидки.
 *
 * Категорически гарантирует, что сумма скидки всегда выражена в копейках и не является отрицательной.
 * Это предотвращает любые финансовые уязвимости, связанные с математическими манипуляциями.
 */
final readonly class DiscountAmount
{
    /**
     * Инициализирует и строго валидирует абсолютную сумму скидки в копейках.
     *
     * @param int $kopecks Сумма скидки в копейках.
     * @throws InvalidArgumentException Если сумма скидки отрицательная.
     */
    public function __construct(
        public int $kopecks
    ) {
        if ($kopecks < 0) {
            throw new InvalidArgumentException('Сумма скидки категорически не может быть отрицательной.');
        }
    }

    /**
     * Безупречно возвращает целочисленное значение суммы скидки в копейках.
     *
     * @return int Финансово точная сумма скидки.
     */
    public function getKopecks(): int
    {
        return $this->kopecks;
    }
}
