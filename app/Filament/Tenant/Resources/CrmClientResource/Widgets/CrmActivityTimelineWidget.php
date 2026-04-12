<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CrmClientResource\Widgets;

use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmInteraction;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

/**
 * CrmActivityTimelineWidget — хронологический таймлайн
 * всех взаимодействий CRM-клиента.
 *
 * Показывает последние 50 взаимодействий: звонки, письма,
 * встречи, покупки, жалобы и т.д. Группируется по дням.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmActivityTimelineWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.crm-activity-timeline';

    public ?CrmClient $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    /**
     * Получить взаимодействия, сгруппированные по дням.
     *
     * @return array<string, Collection<int, CrmInteraction>>
     */
    public function getGroupedInteractions(): array
    {
        if ($this->record === null) {
            return [];
        }

        $interactions = CrmInteraction::query()
            ->where('crm_client_id', $this->record->id)
            ->where('tenant_id', $this->record->tenant_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $grouped = [];
        foreach ($interactions as $interaction) {
            $dateKey = $interaction->created_at->format('Y-m-d');
            $grouped[$dateKey][] = $interaction;
        }

        return $grouped;
    }

    /**
     * Иконка для типа взаимодействия.
     */
    public static function getIconForType(string $type): string
    {
        return match ($type) {
            'call' => 'heroicon-o-phone',
            'email' => 'heroicon-o-envelope',
            'meeting' => 'heroicon-o-calendar-days',
            'purchase' => 'heroicon-o-shopping-cart',
            'complaint' => 'heroicon-o-exclamation-triangle',
            'review' => 'heroicon-o-star',
            'support' => 'heroicon-o-lifebuoy',
            'visit' => 'heroicon-o-map-pin',
            'chat' => 'heroicon-o-chat-bubble-left-right',
            'sms' => 'heroicon-o-device-phone-mobile',
            'return' => 'heroicon-o-arrow-uturn-left',
            default => 'heroicon-o-document-text',
        };
    }

    /**
     * Цвет для типа взаимодействия.
     */
    public static function getColorForType(string $type): string
    {
        return match ($type) {
            'purchase' => 'success',
            'complaint' => 'danger',
            'return' => 'warning',
            'call' => 'info',
            'email' => 'primary',
            'meeting' => 'primary',
            'review' => 'warning',
            'support' => 'info',
            default => 'gray',
        };
    }

    /**
     * Русская метка типа.
     */
    public static function getLabelForType(string $type): string
    {
        return match ($type) {
            'call' => 'Звонок',
            'email' => 'E-mail',
            'meeting' => 'Встреча',
            'purchase' => 'Покупка',
            'complaint' => 'Жалоба',
            'review' => 'Отзыв',
            'support' => 'Поддержка',
            'visit' => 'Визит',
            'chat' => 'Чат',
            'sms' => 'SMS',
            'return' => 'Возврат',
            default => ucfirst($type),
        };
    }

    /**
     * Русская метка направления.
     */
    public static function getLabelForDirection(string $direction): string
    {
        return match ($direction) {
            'inbound' => 'Входящее',
            'outbound' => 'Исходящее',
            'internal' => 'Внутреннее',
            default => $direction,
        };
    }
}
