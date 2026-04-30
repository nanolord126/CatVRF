<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Marketplace\Domain\ValueObjects\VerticalSource;

/**
 * Фабрика адаптеров для интеграции с вертикалями
 */
interface VerticalAdapterFactoryInterface
{
    /**
     * Получить адаптер для вертикали
     */
    public function getAdapter(VerticalSource $source): ?VerticalAdapterInterface;

    /**
     * Зарегистрировать адаптер для вертикали
     */
    public function registerAdapter(VerticalSource $source, VerticalAdapterInterface $adapter): void;

    /**
     * Проверить наличие адаптера для вертикали
     */
    public function hasAdapter(VerticalSource $source): bool;
}
