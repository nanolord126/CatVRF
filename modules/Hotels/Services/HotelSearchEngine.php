<?php

namespace Modules\Hotels\Services;

use Modules\Hotels\Models\Hotel;
use Modules\Hotels\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HotelSearchEngine
{
    /**
     * Платформенный поиск по отелям с учетом фильтров.
     */
    public function search(array $filters): Builder
    {
        $query = Hotel::query()->where('is_active', true);

        // 1. Гео-фильтр (Радиус от точки)
        if (isset($filters['lat'], $filters['lng'])) {
            $radius = $filters['radius'] ?? 10; // км
            $query->whereRaw("
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?
            ", [$filters['lat'], $filters['lng'], $filters['lat'], $radius]);
        }

        // 2. Звездность
        if (isset($filters['stars'])) {
            $query->whereIn('stars', (array) $filters['stars']);
        }

        // 3. Категория объекта (Отель, Хостел, Глэмпинг, Апартаменты, Базы отдыха)
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // 3a. Специфические фильтры загородного отдыха
        if (isset($filters['has_fishing'])) $query->where('has_fishing', true);
        if (isset($filters['has_zoo'])) $query->where('has_zoo', true);
        if (isset($filters['has_forest_access'])) $query->where('has_forest_access', true);

        // 3a-2. Инфраструктура территории
        if (isset($filters['has_gym'])) $query->where('has_gym', true);
        if (isset($filters['has_pool'])) $query->where('has_pool', true);
        if (isset($filters['has_spa'])) $query->where('has_spa', true);
        if (isset($filters['has_restaurant'])) $query->where('has_restaurant', true);
        if (isset($filters['has_shop'])) $query->where('has_shop', true);
        if (isset($filters['has_flowers_shop'])) $query->where('has_flowers_shop', true);

        // 3b. Дистанционные фильтры (Инфраструктура)
        $distFilters = [
            'max_sea' => 'distance_to_sea',
            'max_center' => 'distance_to_center',
            'max_pharmacy' => 'distance_to_pharmacy',
            'max_hospital' => 'distance_to_hospital',
            'max_landmark' => 'distance_to_landmark',
            'max_church' => 'distance_to_church',
        ];

        foreach ($distFilters as $filterKey => $column) {
            if (isset($filters[$filterKey])) {
                $query->where($column, '<=', (float) $filters[$filterKey]);
            }
        }

        // 4. Наличие удобств (Amenity Filter)
        if (isset($filters['amenities']) && is_array($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // 5. Фильтр по доступности номеров (Availability & Price)
        if (isset($filters['price_min']) || isset($filters['price_max']) || isset($filters['capacity']) || isset($filters['sq_meters_min'])) {
            $query->whereHas('rooms', function (Builder $q) use ($filters) {
                if (isset($filters['price_min'])) $q->where('price', '>=', $filters['price_min']);
                if (isset($filters['price_max'])) $q->where('price', '<=', $filters['price_max']);
                if (isset($filters['capacity'])) $q->where('capacity', '>=', $filters['capacity']);
                if (isset($filters['sq_meters_min'])) $q->where('square_meters', '>=', $filters['sq_meters_min']);
                
                // Фильтры удобств в номере
                if (isset($filters['room_balcony'])) $q->where('has_balcony', true);
                if (isset($filters['room_kitchen'])) $q->where('has_kitchen', true);
                if (isset($filters['room_ac'])) $q->where('has_air_con', true);
                
                $q->where('status', 'available');
            });
        }

        return $query;
    }

    /**
     * Определение типа объекта для классификации в Meta-OS.
     */
    public function getPossibleObjectTypes(): array
    {
        return [
            'hotel' => 'Классический отель',
            'apartment' => 'Апартаменты / Квартира посуточно',
            'hostel' => 'Хостел',
            'glamping' => 'Глэмпинг / Эко-отель',
            'sanatorium' => 'Санаторий / Пансионат',
            'country_house' => 'Загородный дом / Коттедж / Вилла',
            'recreation_center' => 'База отдыха / Турбаза',
            'fishing_base' => 'Рыболовная база / Охотничье хозяйство',
            'zoo_resort' => 'База отдыха с зоозоной / Эко-ферма',
        ];
    }
}
