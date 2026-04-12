<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmClientResource\Widgets;

use App\Domains\CRM\Models\CrmClient;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * CrmVerticalProfileWidget — динамический виджет вертикального
 * профиля CRM-клиента на странице ViewCrmClient.
 *
 * Показывает данные из crm_{vertical}_profiles в зависимости от
 * вертикали клиента. Каждая вертикаль — свой набор полей.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmVerticalProfileWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.crm-vertical-profile';

    public ?CrmClient $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    /**
     * Вычисление данных профиля для Blade.
     */
    public function getProfileData(): array
    {
        if ($this->record === null) {
            return ['vertical' => null, 'profile' => null, 'fields' => []];
        }

        $vertical = $this->record->vertical;
        $profile = $this->record->verticalProfile();

        if ($profile === null || $vertical === null) {
            return ['vertical' => $vertical, 'profile' => null, 'fields' => []];
        }

        $fields = $this->getFieldsForVertical($vertical, $profile);

        return [
            'vertical' => $vertical,
            'profile' => $profile,
            'fields' => $fields,
        ];
    }

    /**
     * Маппинг полей профиля по вертикалям.
     * Возвращает массив ['label' => 'value'].
     */
    private function getFieldsForVertical(string $vertical, Model $profile): array
    {
        return match ($vertical) {
            'beauty' => $this->beautyFields($profile),
            'hotel' => $this->hotelFields($profile),
            'flowers' => $this->flowersFields($profile),
            'auto' => $this->autoFields($profile),
            'food' => $this->foodFields($profile),
            'furniture' => $this->furnitureFields($profile),
            'fashion' => $this->fashionFields($profile),
            'fitness' => $this->fitnessFields($profile),
            'real_estate' => $this->realEstateFields($profile),
            'medical' => $this->medicalFields($profile),
            'education' => $this->educationFields($profile),
            'travel' => $this->travelFields($profile),
            'pet' => $this->petFields($profile),
            'taxi' => $this->taxiFields($profile),
            'electronics' => $this->electronicsFields($profile),
            'events' => $this->eventsFields($profile),
            default => $this->genericFields($profile),
        };
    }

    private function beautyFields(Model $profile): array
    {
        return [
            'Тип кожи' => $profile->skin_type ?? '–',
            'Тип волос' => $profile->hair_type ?? '–',
            'Аллергии' => $this->formatJson($profile->allergies),
            'Любимые мастера' => $this->formatJson($profile->favorite_masters),
            'Предпочтения услуг' => $this->formatJson($profile->preferred_services),
            'Частота посещений' => $profile->visit_frequency ?? '–',
            'Заметки мастера' => $profile->master_notes ?? '–',
        ];
    }

    private function hotelFields(Model $profile): array
    {
        return [
            'Тип номера' => $profile->preferred_room_type ?? '–',
            'Кол-во ночей (всего)' => (string) ($profile->total_nights ?? 0),
            'Программа лояльности' => $profile->loyalty_program ?? '–',
            'Диетические предп.' => $this->formatJson($profile->dietary_preferences),
            'Паспортные данные' => $profile->passport_data ? '✅ Заполнены' : '–',
            'Спец. запросы' => $this->formatJson($profile->special_requests),
            'Заметки' => $profile->concierge_notes ?? '–',
        ];
    }

    private function flowersFields(Model $profile): array
    {
        return [
            'Любимые цветы' => $this->formatJson($profile->favorite_flowers),
            'Аллергии' => $this->formatJson($profile->allergies),
            'Важные даты' => $this->formatJson($profile->important_dates),
            'Адреса доставки' => $this->formatJson($profile->delivery_addresses),
            'Стиль букетов' => $profile->preferred_style ?? '–',
            'Частота заказов' => $profile->order_frequency ?? '–',
        ];
    }

    private function autoFields(Model $profile): array
    {
        return [
            'Автомобили' => $this->formatJson($profile->vehicles),
            'Предп. бренды' => $this->formatJson($profile->preferred_brands),
            'Сервисная история' => $this->formatJson($profile->service_history),
            'Тип топлива' => $profile->fuel_type ?? '–',
            'Страховка до' => $profile->insurance_expiry ?? '–',
            'ТО до' => $profile->next_maintenance_date ?? '–',
        ];
    }

    private function foodFields(Model $profile): array
    {
        return [
            'Диетические предп.' => $this->formatJson($profile->dietary_preferences),
            'Аллергены' => $this->formatJson($profile->allergens),
            'Любимые кухни' => $this->formatJson($profile->favorite_cuisines),
            'Любимые рестораны' => $this->formatJson($profile->favorite_restaurants),
            'Цель калорий/день' => (string) ($profile->calorie_goal ?? '–'),
            'Бюджет/заказ' => $profile->budget_per_order ? number_format((float) $profile->budget_per_order, 0, ',', ' ') . ' ₽' : '–',
        ];
    }

    private function furnitureFields(Model $profile): array
    {
        return [
            'Стиль интерьера' => $profile->interior_style ?? '–',
            'Параметры комнат' => $this->formatJson($profile->room_dimensions),
            'Цветовая гамма' => $this->formatJson($profile->color_preferences),
            'Бюджет' => $profile->budget_range ?? '–',
            'Сохр. дизайны' => $this->formatJson($profile->saved_designs),
            'Предп. материалы' => $this->formatJson($profile->preferred_materials),
        ];
    }

    private function fashionFields(Model $profile): array
    {
        return [
            'Размеры' => $this->formatJson($profile->sizes),
            'Цветотип' => $profile->color_type ?? '–',
            'Стиль' => $profile->style_preference ?? '–',
            'Любимые бренды' => $this->formatJson($profile->favorite_brands),
            'Капсулы' => $this->formatJson($profile->capsules),
            'AR-примерок' => (string) ($profile->ar_try_ons_count ?? 0),
        ];
    }

    private function fitnessFields(Model $profile): array
    {
        return [
            'Цель' => $profile->fitness_goal ?? '–',
            'Тип тела' => $profile->body_type ?? '–',
            'Уровень' => $profile->experience_level ?? '–',
            'Противопоказания' => $this->formatJson($profile->health_restrictions),
            'Абонемент' => $profile->membership_type ?? '–',
            'Тренер' => $profile->preferred_trainer ?? '–',
            'Посещений/мес' => (string) ($profile->visits_per_month ?? 0),
        ];
    }

    private function realEstateFields(Model $profile): array
    {
        return [
            'Тип недвижимости' => $profile->property_type_interest ?? '–',
            'Бюджет' => $profile->budget_range ?? '–',
            'Предп. районы' => $this->formatJson($profile->preferred_districts),
            'Площадь' => $profile->desired_area ?? '–',
            'Ипотека' => $profile->mortgage_approved ? '✅ Одобрена' : '–',
            'Объекты в избранном' => (string) ($profile->saved_properties_count ?? 0),
        ];
    }

    private function medicalFields(Model $profile): array
    {
        return [
            'Группа крови' => $profile->blood_type ?? '–',
            'Аллергии' => $this->formatJson($profile->allergies),
            'Хр. заболевания' => $this->formatJson($profile->chronic_conditions),
            'Лекарства' => $this->formatJson($profile->medications),
            'Страховка' => $profile->insurance_provider ?? '–',
            'Полис до' => $profile->insurance_expiry ?? '–',
            'Последний приём' => $profile->last_visit_date ?? '–',
        ];
    }

    private function educationFields(Model $profile): array
    {
        return [
            'Направление' => $profile->learning_direction ?? '–',
            'Уровень' => $profile->current_level ?? '–',
            'Формат' => $profile->preferred_format ?? '–',
            'Курсы пройдены' => (string) ($profile->completed_courses_count ?? 0),
            'Сертификаты' => $this->formatJson($profile->certificates),
            'Цели обучения' => $this->formatJson($profile->learning_goals),
        ];
    }

    private function travelFields(Model $profile): array
    {
        return [
            'Тип путешествий' => $profile->travel_style ?? '–',
            'Визы' => $this->formatJson($profile->visas),
            'Паспорт до' => $profile->passport_expiry ?? '–',
            'Любимые страны' => $this->formatJson($profile->favorite_destinations),
            'Поездок всего' => (string) ($profile->total_trips ?? 0),
            'Программа лояльности' => $this->formatJson($profile->loyalty_programs),
        ];
    }

    private function petFields(Model $profile): array
    {
        return [
            'Питомцы' => $this->formatJson($profile->pets),
            'Вет. клиника' => $profile->preferred_vet_clinic ?? '–',
            'Вакцинации' => $this->formatJson($profile->vaccinations),
            'Корм' => $profile->preferred_food_brand ?? '–',
            'Стрижка' => $profile->grooming_frequency ?? '–',
            'Заметки' => $profile->special_needs ?? '–',
        ];
    }

    private function taxiFields(Model $profile): array
    {
        return [
            'Домашний адрес' => $profile->home_address ?? '–',
            'Рабочий адрес' => $profile->work_address ?? '–',
            'Любимые маршруты' => $this->formatJson($profile->favorite_routes),
            'Класс авто' => $profile->preferred_car_class ?? '–',
            'Поездок всего' => (string) ($profile->total_rides ?? 0),
            'Рейтинг пассажира' => $profile->passenger_rating ?? '–',
        ];
    }

    private function electronicsFields(Model $profile): array
    {
        return [
            'Устройства' => $this->formatJson($profile->devices),
            'Экосистема' => $profile->preferred_ecosystem ?? '–',
            'Гарантии' => $this->formatJson($profile->warranties),
            'Trade-in история' => $this->formatJson($profile->trade_in_history),
            'Подписка на новинки' => $profile->subscribed_to_releases ? '✅' : '–',
        ];
    }

    private function eventsFields(Model $profile): array
    {
        return [
            'Тип мероприятий' => $this->formatJson($profile->preferred_event_types),
            'Бюджет' => $profile->typical_budget ?? '–',
            'Кол-во гостей (обычно)' => (string) ($profile->typical_guest_count ?? '–'),
            'Предп. площадки' => $this->formatJson($profile->preferred_venues),
            'Организовано всего' => (string) ($profile->total_events ?? 0),
            'Предп. поставщики' => $this->formatJson($profile->preferred_vendors),
        ];
    }

    private function genericFields(Model $profile): array
    {
        $attributes = $profile->toArray();
        $result = [];

        $exclude = ['id', 'crm_client_id', 'tenant_id', 'correlation_id', 'created_at', 'updated_at'];
        foreach ($attributes as $key => $value) {
            if (in_array($key, $exclude, true)) {
                continue;
            }
            $label = str_replace('_', ' ', ucfirst($key));
            $result[$label] = is_array($value) ? $this->formatJson($value) : (string) ($value ?? '–');
        }

        return $result;
    }

    private function formatJson(mixed $data): string
    {
        if ($data === null || $data === []) {
            return '–';
        }

        if (is_array($data)) {
            if (array_is_list($data)) {
                return implode(', ', array_map(fn ($item) => is_array($item)
                    ? json_encode($item, JSON_UNESCAPED_UNICODE)
                    : (string) $item, $data));
            }

            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return (string) $data;
    }
}
