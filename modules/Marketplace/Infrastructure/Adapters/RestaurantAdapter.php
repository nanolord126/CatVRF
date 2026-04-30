<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Adapters;

use Modules\Restaurant\Domain\Entities\Dish;
use Modules\Restaurant\Domain\Repositories\DishRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;

/**
 * Адаптер для интеграции с вертикалью Restaurant
 */
final class RestaurantAdapter implements VerticalAdapterInterface
{
    public function __construct(
        private readonly DishRepositoryInterface $dishRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        $this->logger->info('Fetching Restaurant dishes', ['filters' => $filters, 'limit' => $limit]);

        $dishes = $this->dishRepository->findActive($limit);

        $sources = [];
        foreach ($dishes as $dish) {
            try {
                $sources[] = $this->convertDishToArray($dish);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to convert dish', [
                    'dish_id' => $dish->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Fetched Restaurant dishes', ['count' => count($sources)]);

        return $sources;
    }

    public function fetchSingle(int $sourceId, array $filters = []): ?array
    {
        $this->logger->info('Fetching single Restaurant dish', ['dish_id' => $sourceId]);

        $dish = $this->dishRepository->findById($sourceId);
        if ($dish === null) {
            return null;
        }

        return $this->convertDishToArray($dish);
    }

    public function countSources(array $filters = []): int
    {
        return $this->dishRepository->countActive();
    }

    public function validateSource(array $source): bool
    {
        $required = ['id', 'title', 'price', 'category'];
        foreach ($required as $field) {
            if (!isset($source[$field])) {
                return false;
            }
        }

        return !empty($source['title']) && $source['price'] > 0;
    }

    public function getSupportedEntityTypes(): array
    {
        return ['product', 'dish'];
    }

    public function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
            'is_available' => true,
            'auto_publish' => true,
        ];
    }

    private function convertDishToArray(Dish $dish): array
    {
        return [
            'id' => $dish->id,
            'title' => $dish->name,
            'description' => $dish->description ?? '',
            'price' => $dish->price,
            'currency' => 'RUB',
            'category' => $this->mapCategory($dish->category),
            'type' => 'product',
            'tags' => array_merge(
                $dish->tags ?? [],
                $dish->isVegetarian ? ['vegetarian'] : [],
                $dish->isSpicy ? ['spicy'] : [],
            ),
            'images' => $dish->images ?? [],
            'attributes' => [
                'restaurant_id' => $dish->restaurantId,
                'cuisine' => $dish->cuisine,
                'calories' => $dish->calories,
                'preparation_time' => $dish->preparationTimeMinutes,
                'is_vegetarian' => $dish->isVegetarian,
                'is_spicy' => $dish->isSpicy,
            ],
            'metadata' => [
                'vertical' => VerticalSource::RESTAURANT->value,
                'source_type' => 'dish',
                'supports_delivery' => true,
                'created_at' => $dish->createdAt->format('Y-m-d H:i:s'),
            ],
            'tenant_id' => $dish->tenantId,
            'business_group_id' => $dish->businessGroupId,
        ];
    }

    private function mapCategory(?string $category): string
    {
        $mapping = [
            'appetizer' => 'starters',
            'main_course' => 'main',
            'dessert' => 'desserts',
            'beverage' => 'drinks',
            'soup' => 'soups',
            'salad' => 'salads',
            'pizza' => 'pizza',
            'sushi' => 'sushi',
        ];

        return $mapping[$category ?? ''] ?? 'food';
    }
}
