<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

/**
 * Фабрика адаптеров для интеграции с вертикалями
 */
final class VerticalAdapterFactory implements VerticalAdapterFactoryInterface
{
    /** @var array<string, VerticalAdapterInterface> */
    private array $adapters = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function getAdapter(VerticalSource $source): ?VerticalAdapterInterface
    {
        $key = $source->value;

        if (!isset($this->adapters[$key])) {
            $this->logger->warning('Adapter not registered for vertical', ['source' => $key]);
            return null;
        }

        return $this->adapters[$key];
    }

    public function registerAdapter(VerticalSource $source, VerticalAdapterInterface $adapter): void
    {
        $key = $source->value;
        $this->adapters[$key] = $adapter;

        $this->logger->info('Adapter registered for vertical', [
            'source' => $key,
            'adapter_class' => get_class($adapter),
        ]);
    }

    public function hasAdapter(VerticalSource $source): bool
    {
        return isset($this->adapters[$source->value]);
    }

    /**
     * Получить все зарегистрированные адаптеры
     *
     * @return array<string, VerticalAdapterInterface>
     */
    public function getAllAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Зарегистрировать адаптеры для всех поддерживаемых вертикалей
     * Должен вызываться при регистрации сервис-провайдера
     */
    public function registerDefaultAdapters(
        ?BeautyAdapter $beautyAdapter = null,
        ?RestaurantAdapter $restaurantAdapter = null,
    ): void {
        if ($beautyAdapter !== null) {
            $this->registerAdapter(VerticalSource::BEAUTY, $beautyAdapter);
        }

        if ($restaurantAdapter !== null) {
            $this->registerAdapter(VerticalSource::RESTAURANT, $restaurantAdapter);
        }

        // Другие адаптеры будут добавлены по мере реализации вертикалей
        // FashionAdapter, HotelsAdapter, FitnessAdapter и т.д.
    }
}
