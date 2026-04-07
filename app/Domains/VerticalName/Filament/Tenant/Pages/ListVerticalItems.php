<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Tenant\Pages;

use App\Domains\VerticalName\Filament\Tenant\VerticalItemResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

/**
 * Filament Page: список товаров VerticalName.
 *
 * CANON 2026 — Layer 9: Filament Tenant Panel.
 * Отображает все товары текущего tenant с фильтрацией и поиском.
 * Tenant-scoping обеспечивается автоматически через глобальный scope модели.
 *
 * Функциональность:
 *   — Пагинированный список с сортировкой по дате создания.
 *   — Быстрый поиск по имени, категории и SKU.
 *   — Фильтры: статус, B2B, наличие, активность.
 *   — Кнопка «Создать товар» в header.
 *
 * @package App\Domains\VerticalName\Filament\Tenant\Pages
 */
final class ListVerticalItems extends ListRecords
{
    /**
     * Связанный Filament-ресурс.
     *
     * @var string
     */
    protected static string $resource = VerticalItemResource::class;

    /**
     * Заголовок страницы, отображаемый в навигации и хлебных крошках.
     *
     * @var string|null
     */
    protected ?string $heading = 'Товары VerticalName';

    /**
     * Подзаголовок с кратким описанием страницы.
     *
     * @var string|null
     */
    protected ?string $subheading = 'Управление товарами и каталогом вашего бизнеса.';

    /**
     * Actions в header страницы.
     *
     * Включает кнопку создания нового товара.
     *
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать товар')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    /**
     * Виджеты, отображаемые над таблицей.
     *
     * Могут включать метрики: общее количество товаров, средняя цена,
     * количество без остатков, B2B-товары и т.д.
     *
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
