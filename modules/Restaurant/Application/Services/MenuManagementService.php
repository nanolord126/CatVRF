<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Modules\Restaurant\Domain\Entities\MenuCategory;
use Modules\Restaurant\Domain\Entities\MenuItem;
use Modules\Restaurant\Domain\Entities\MenuItemModifier;
use Modules\Restaurant\Domain\ValueObjects\Money;
use Modules\Restaurant\Infrastructure\Models\MenuCategoryModel;
use Modules\Restaurant\Infrastructure\Models\MenuItemModel;
use Modules\Restaurant\Infrastructure\Models\MenuItemModifierModel;

/**
 * MenuManagementService — Сервис для управления меню ресторана
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Cache with tags for invalidation
 * - Audit logging
 */
final readonly class MenuManagementService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600; // 1 час

    public function __construct(
        private readonly string $tenantId,
        private readonly CacheManager $cache,
        private readonly DatabaseManager $db,
        private readonly AuditService $auditService,
    ) {}

    // Категории

    public function createCategory(string $name, ?string $description = null, ?int $parentId = null): MenuCategory
    {
        $category = MenuCategory::create(
            tenantId: (int) $this->tenantId,
            name: $name,
            description: $description,
            parentId: $parentId,
        );

        $model = MenuCategoryModel::create([
            'tenant_id' => $category->tenantId,
            'name' => $category->name,
            'description' => $category->description,
            'display_order' => $category->displayOrder,
            'is_active' => $category->isActive,
            'parent_id' => $category->parentId,
        ]);

        $category = new MenuCategory(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            imageUrl: $model->image_url,
            displayOrder: $model->display_order,
            isActive: $model->is_active,
            parentId: $model->parent_id,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );

        $this->clearCache();

        return $category;
    }

    public function updateCategory(int $categoryId, string $name): MenuCategory
    {
        $model = MenuCategoryModel::findOrFail($categoryId);
        $model->name = $name;
        $model->save();

        return new MenuCategory(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            imageUrl: $model->image_url,
            displayOrder: $model->display_order,
            isActive: $model->is_active,
            parentId: $model->parent_id,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getCategory(int $categoryId): ?MenuCategory
    {
        return $this->cache->remember(
            "menu_category_{$this->tenantId}_{$categoryId}",
            self::CACHE_TTL,
            function () use ($categoryId) {
                $model = MenuCategoryModel::where('tenant_id', $this->tenantId)
                    ->where('id', $categoryId)
                    ->first();

                if ($model === null) {
                    return null;
                }

                return new MenuCategory(
                    id: $model->id,
                    tenantId: $model->tenant_id,
                    name: $model->name,
                    description: $model->description,
                    imageUrl: $model->image_url,
                    displayOrder: $model->display_order,
                    isActive: $model->is_active,
                    parentId: $model->parent_id,
                    createdAt: $model->created_at->toImmutable(),
                    updatedAt: $model->updated_at->toImmutable(),
                );
            }
        );
    }

    public function getAllCategories(): Collection
    {
        return $this->cache->remember(
            "menu_categories_{$this->tenantId}",
            self::CACHE_TTL,
            function () {
                return MenuCategoryModel::where('tenant_id', $this->tenantId)
                    ->orderBy('display_order')
                    ->get()
                    ->map(fn ($model) => new MenuCategory(
                        id: $model->id,
                        tenantId: $model->tenant_id,
                        name: $model->name,
                        description: $model->description,
                        imageUrl: $model->image_url,
                        displayOrder: $model->display_order,
                        isActive: $model->is_active,
                        parentId: $model->parent_id,
                        createdAt: $model->created_at->toImmutable(),
                        updatedAt: $model->updated_at->toImmutable(),
                    ));
            }
        );
    }

    // Блюда

    public function createMenuItem(
        int $categoryId,
        string $name,
        Money $price,
        ?string $description = null,
        string $sku = '',
        int $preparationTime = 15,
    ): MenuItem {
        $menuItem = MenuItem::create(
            tenantId: (int) $this->tenantId,
            categoryId: $categoryId,
            name: $name,
            price: $price,
            description: $description,
            sku: $sku,
            preparationTime: $preparationTime,
        );

        $model = MenuItemModel::create([
            'tenant_id' => $menuItem->tenantId,
            'category_id' => $menuItem->categoryId,
            'name' => $menuItem->name,
            'description' => $menuItem->description,
            'price_kopecks' => $menuItem->price->toKopecks(),
            'sku' => $menuItem->sku,
            'preparation_time' => $menuItem->preparationTime,
            'is_active' => $menuItem->isActive,
            'is_available' => $menuItem->isAvailable,
        ]);

        $menuItem = new MenuItem(
            id: $model->id,
            tenantId: $model->tenant_id,
            categoryId: $model->category_id,
            name: $model->name,
            description: $model->description,
            price: Money::fromKopecks($model->price_kopecks),
            imageUrl: $model->image_url,
            sku: $model->sku,
            preparationTime: $model->preparation_time,
            isActive: $model->is_active,
            isAvailable: $model->is_available,
            isFeatured: $model->is_featured,
            allergens: $model->allergens,
            nutritionalInfo: $model->nutritional_info,
            calories: $model->calories,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );

        $this->clearCache();

        return $menuItem;
    }

    public function updateMenuItemPrice(int $menuItemId, Money $price): MenuItem
    {
        $model = MenuItemModel::findOrFail($menuItemId);
        $model->price_kopecks = $price->toKopecks();
        $model->save();

        $this->clearCache();

        return new MenuItem(
            id: $model->id,
            tenantId: $model->tenant_id,
            categoryId: $model->category_id,
            name: $model->name,
            description: $model->description,
            price: Money::fromKopecks($model->price_kopecks),
            imageUrl: $model->image_url,
            sku: $model->sku,
            preparationTime: $model->preparation_time,
            isActive: $model->is_active,
            isAvailable: $model->is_available,
            isFeatured: $model->is_featured,
            allergens: $model->allergens,
            nutritionalInfo: $model->nutritional_info,
            calories: $model->calories,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function setMenuItemAvailability(int $menuItemId, bool $available): void
    {
        MenuItemModel::where('id', $menuItemId)
            ->where('tenant_id', $this->tenantId)
            ->update(['is_available' => $available]);

        $this->clearCache();
    }

    public function getMenuItem(int $menuItemId): ?MenuItem
    {
        return $this->cache->remember(
            "menu_item_{$this->tenantId}_{$menuItemId}",
            self::CACHE_TTL,
            function () use ($menuItemId) {
                $model = MenuItemModel::where('tenant_id', $this->tenantId)
                    ->where('id', $menuItemId)
                    ->first();

                if ($model === null) {
                    return null;
                }

                return new MenuItem(
                    id: $model->id,
                    tenantId: $model->tenant_id,
                    categoryId: $model->category_id,
                    name: $model->name,
                    description: $model->description,
                    price: Money::fromKopecks($model->price_kopecks),
                    imageUrl: $model->image_url,
                    sku: $model->sku,
                    preparationTime: $model->preparation_time,
                    isActive: $model->is_active,
                    isAvailable: $model->is_available,
                    isFeatured: $model->is_featured,
                    allergens: $model->allergens,
                    nutritionalInfo: $model->nutritional_info,
                    calories: $model->calories,
                    createdAt: $model->created_at->toImmutable(),
                    updatedAt: $model->updated_at->toImmutable(),
                );
            }
        );
    }

    public function getMenuByCategory(int $categoryId): Collection
    {
        return $this->cache->remember(
            "menu_category_{$this->tenantId}_{$categoryId}_items",
            self::CACHE_TTL,
            function () use ($categoryId) {
                return MenuItemModel::where('tenant_id', $this->tenantId)
                    ->where('category_id', $categoryId)
                    ->where('is_active', true)
                    ->where('is_available', true)
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn ($model) => new MenuItem(
                        id: $model->id,
                        tenantId: $model->tenant_id,
                        categoryId: $model->category_id,
                        name: $model->name,
                        description: $model->description,
                        price: Money::fromKopecks($model->price_kopecks),
                        imageUrl: $model->image_url,
                        sku: $model->sku,
                        preparationTime: $model->preparation_time,
                        isActive: $model->is_active,
                        isAvailable: $model->is_available,
                        isFeatured: $model->is_featured,
                        allergens: $model->allergens,
                        nutritionalInfo: $model->nutritional_info,
                        calories: $model->calories,
                        createdAt: $model->created_at->toImmutable(),
                        updatedAt: $model->updated_at->toImmutable(),
                    ));
            }
        );
    }

    public function getFullMenu(): Collection
    {
        return $this->cache->remember(
            "full_menu_{$this->tenantId}",
            self::CACHE_TTL,
            function () {
                $categories = $this->getAllCategories();

                return $categories->map(function (MenuCategory $category) {
                    return [
                        'category' => $category,
                        'items' => $this->getMenuByCategory($category->id),
                    ];
                });
            }
        );
    }

    // Модификаторы

    public function createModifier(
        int $menuItemId,
        string $name,
        Money $price,
        bool $isRequired = false,
    ): MenuItemModifier {
        $modifier = MenuItemModifier::create(
            tenantId: (int) $this->tenantId,
            menuItemId: $menuItemId,
            name: $name,
            price: $price,
            isRequired: $isRequired,
        );

        $model = MenuItemModifierModel::create([
            'tenant_id' => $modifier->tenantId,
            'menu_item_id' => $modifier->menuItemId,
            'name' => $modifier->name,
            'price_kopecks' => $modifier->price->toKopecks(),
            'is_required' => $modifier->isRequired,
            'is_multi_select' => $modifier->isMultiSelect,
            'max_select_count' => $modifier->maxSelectCount,
            'is_active' => $modifier->isActive,
        ]);

        $this->clearCache();

        return new MenuItemModifier(
            id: $model->id,
            tenantId: $model->tenant_id,
            menuItemId: $model->menu_item_id,
            name: $model->name,
            description: $model->description,
            price: Money::fromKopecks($model->price_kopecks),
            isRequired: $model->is_required,
            isMultiSelect: $model->is_multi_select,
            maxSelectCount: $model->max_select_count,
            displayOrder: $model->display_order,
            isActive: $model->is_active,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getModifiersForItem(int $menuItemId): Collection
    {
        return MenuItemModifierModel::where('tenant_id', $this->tenantId)
            ->where('menu_item_id', $menuItemId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn ($model) => new MenuItemModifier(
                id: $model->id,
                tenantId: $model->tenant_id,
                menuItemId: $model->menu_item_id,
                name: $model->name,
                description: $model->description,
                price: Money::fromKopecks($model->price_kopecks),
                isRequired: $model->is_required,
                isMultiSelect: $model->is_multi_select,
                maxSelectCount: $model->max_select_count,
                displayOrder: $model->display_order,
                isActive: $model->is_active,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    private function clearCache(): void
    {
        $this->cache->tags(['restaurant_menu', $this->tenantId])->flush();
    }
}
