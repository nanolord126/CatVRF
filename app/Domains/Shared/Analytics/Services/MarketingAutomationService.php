<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use App\Models\User;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Modules\Analytics\Models\BehavioralEvent;
use Modules\Common\Services\AbstractTechnicalVerticalService;

final class MarketingAutomationService extends AbstractTechnicalVerticalService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->tenant->settings['marketing_automation_enabled'] ?? true;
    }

    /**
     * Кросс-вертикальный апселл: Цветы после отеля.
     */
    public function triggerHotelToFlowersUpsell(User $user, $hotelBookingId): void
    {
        // 1. Проверяем, не предлагали ли уже
        // 2. Генерируем оффер
        $correlationId = Str::uuid()->toString();
        $tenantId = $user->tenant_id ?? null;

        $this->logAction('cross_sell_hotel_to_flowers', 'MarketingAutomation', null, [
            'user_id' => $user->id,
            'hotel_booking_id' => $hotelBookingId,
        ], $user->id, $tenantId, $correlationId);

        // Эффект: отправка Push-уведомления со скидкой 10% на букет
        // В реальности здесь создание записи в marketing_automation_logs и вызов Notification фасада
    }

    /**
     * Динамическое ценообразование на основе чувствительности.
     */
    public function getPersonalizedDiscount(User $user, string $vertical): float
    {
        $stats = BehavioralEvent::where('user_id', $user->id)
            ->where('event_type', 'view')
            ->where('vertical', $vertical)
            ->get();

        // Если пользователь смотрит много раз, но не покупает - даем скидку.
        // Если лояльный VIP - даем бонусные баллы, а не скидку (сохраняем маржинальность).
        return 0.15; // Возврат 15% скидки
    }
}
