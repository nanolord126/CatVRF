<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmAutomationDto;
use App\Domains\CRM\Models\CrmAutomation;
use App\Domains\CRM\Models\CrmAutomationLog;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmSegment;
use App\Domains\CRM\Jobs\ExecuteCrmAutomationJob;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

use Illuminate\Log\LogManager;
/**
 * CrmAutomationService — управление триггерными кампаниями CRM.
 * Создание, запуск и логирование автоматизаций маркетинга.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CrmAutomationService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
    
        private readonly LogManager $logger
    ) {}

    /**
     * Создать новую автоматизацию.
     */
    public function createAutomation(CreateCrmAutomationDto $dto): CrmAutomation
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_automation_create',
            amount: 0,
            correlationId: $dto->correlationId
    );

        return $this->db->transaction(function () use ($dto): CrmAutomation {
            $automation = CrmAutomation::query()->create($dto->toArray());

            $this->logger->info('CRM automation created', [
                'automation_id' => $automation->id,
                'trigger_type' => $dto->triggerType,
                'action_type' => $dto->actionType,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_automation_created',
                CrmAutomation::class,
                $automation->id,
                [],
                $dto->toArray(),
                $dto->correlationId
    );

            return $automation;
        });
    }

    /**
     * Проверить и запустить все активные автоматизации для tenant'а.
     * Вызывается Scheduler'ом (ежечасно или по расписанию).
     */
    public function processAutomations(int $tenantId, string $correlationId): int
    {
        $automations = CrmAutomation::query()
            ->forTenant($tenantId)
            ->active()
            ->get();

        $dispatched = 0;

        foreach ($automations as $automation) {
            $eligibleClients = $this->findEligibleClients($automation);

            foreach ($eligibleClients as $client) {
                if ($this->shouldSkipClient($automation, $client)) {
                    continue;
                }

                ExecuteCrmAutomationJob::dispatch(
                    $automation->id,
                    $client->id,
                    $correlationId
    )->onQueue('crm-automations');

                $dispatched++;
            }
        }

        $this->logger->info('CRM automations processed', [
            'tenant_id' => $tenantId,
            'automations_count' => $automations->count(),
            'dispatched' => $dispatched,
            'correlation_id' => $correlationId,
        ]);

        return $dispatched;
    }

    /**
     * Найти клиентов, подходящих под триггер автоматизации.
     */
    public function findEligibleClients(CrmAutomation $automation): Collection
    {
        $config = $automation->trigger_config ?? [];

        $query = CrmClient::query()->forTenant($automation->tenant_id);

        if ($automation->vertical) {
            $query->byVertical($automation->vertical);
        }

        return match ($automation->trigger_type) {
            'birthday' => $this->findBirthdayClients($query, $config),
            'inactivity' => $this->findInactiveClients($query, $config),
            'post_order' => $this->findPostOrderClients($query, $config),
            'post_visit' => $this->findPostVisitClients($query, $config),
            'signup' => $this->findRecentSignups($query, $config),
            'custom_date' => $this->findCustomDateClients($query, $config),
            'abandoned_cart' => $this->findAbandonedCartClients($query, $config),
            default => collect(),
        };
    }

    /**
     * Логирование выполнения автоматизации.
     */
    public function logExecution(
        int $automationId,
        int $clientId,
        string $status,
        array $resultData,
        ?string $errorMessage,
        string $correlationId
    ): CrmAutomationLog {
        $log = CrmAutomationLog::query()->create([
            'crm_automation_id' => $automationId,
            'crm_client_id' => $clientId,
            'correlation_id' => $correlationId,
            'status' => $status,
            'result_data' => $resultData,
            'error_message' => $errorMessage,
            'executed_at' => now(),
        ]);

        // Обновляем счётчики на автоматизации
        $automation = CrmAutomation::query()->find($automationId);

        if ($automation instanceof CrmAutomation) {
            $updates = ['total_sent' => $automation->total_sent + 1];

            if ($status === 'opened') {
                $updates['total_opened'] = $automation->total_opened + 1;
            }
            if ($status === 'clicked') {
                $updates['total_clicked'] = $automation->total_clicked + 1;
            }
            if ($status === 'converted') {
                $updates['total_converted'] = $automation->total_converted + 1;
            }

            $automation->update($updates);
        }

        return $log;
    }

    /**
     * Предустановленные автоматизации для Beauty.
     */
    public function getBeautyPresets(): array
    {
        return [
            [
                'name' => 'Поздравление с днём рождения',
                'trigger_type' => 'birthday',
                'trigger_config' => ['days_before' => 1],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'birthday_beauty', 'bonus_amount' => 500],
            ],
            [
                'name' => 'Реактивация спящих клиентов',
                'trigger_type' => 'inactivity',
                'trigger_config' => ['days_inactive' => 60],
                'action_type' => 'send_sms',
                'action_config' => ['template' => 'reactivation_beauty', 'discount_percent' => 15],
            ],
            [
                'name' => 'Отзыв после визита',
                'trigger_type' => 'post_visit',
                'trigger_config' => ['hours_after' => 24],
                'action_type' => 'send_push',
                'action_config' => ['template' => 'review_request_beauty'],
            ],
            [
                'name' => 'После отмены записи',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['event' => 'appointment_cancelled', 'hours_after' => 2],
                'action_type' => 'send_sms',
                'action_config' => ['template' => 'rebooking_offer'],
            ],
        ];
    }

    /**
     * Предустановленные автоматизации для Hotels.
     */
    public function getHotelPresets(): array
    {
        return [
            [
                'name' => 'Напоминание за 48 часов до заезда',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['event' => 'checkin', 'hours_before' => 48],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'checkin_reminder'],
            ],
            [
                'name' => 'Напоминание за 2 часа до заезда',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['event' => 'checkin', 'hours_before' => 2],
                'action_type' => 'send_push',
                'action_config' => ['template' => 'checkin_final'],
            ],
            [
                'name' => 'Отзыв после выезда',
                'trigger_type' => 'post_visit',
                'trigger_config' => ['hours_after' => 24],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'checkout_review', 'bonus_amount' => 300],
            ],
            [
                'name' => 'Реактивация гостей (90+ дней)',
                'trigger_type' => 'inactivity',
                'trigger_config' => ['days_inactive' => 90],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'hotel_reactivation', 'discount_percent' => 10],
            ],
            [
                'name' => 'Апгрейд номера для лояльных',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['event' => 'checkin', 'hours_before' => 24, 'segment' => 'loyal'],
                'action_type' => 'send_push',
                'action_config' => ['template' => 'room_upgrade_offer'],
            ],
        ];
    }

    /**
     * Предустановленные автоматизации для Flowers.
     */
    public function getFlowerPresets(): array
    {
        return [
            [
                'name' => 'Напоминание о дне рождения',
                'trigger_type' => 'birthday',
                'trigger_config' => ['days_before' => 3, 'source' => 'occasions'],
                'action_type' => 'send_push',
                'action_config' => ['template' => 'birthday_reminder_flowers'],
            ],
            [
                'name' => 'Реактивация (45+ дней)',
                'trigger_type' => 'inactivity',
                'trigger_config' => ['days_inactive' => 45],
                'action_type' => 'send_sms',
                'action_config' => ['template' => 'flower_reactivation', 'discount_percent' => 10],
            ],
            [
                'name' => 'Букет месяца для постоянных',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['day_of_month' => 1, 'segment' => 'loyal'],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'bouquet_of_month'],
            ],
            [
                'name' => 'Корпоративные праздники',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['source' => 'corporate_holidays', 'days_before' => 5],
                'action_type' => 'send_email',
                'action_config' => ['template' => 'corporate_holiday_flowers'],
            ],
            [
                'name' => 'Напоминание о 8 Марта',
                'trigger_type' => 'custom_date',
                'trigger_config' => ['date' => '03-08', 'days_before' => 7],
                'action_type' => 'send_push',
                'action_config' => ['template' => 'march_8_flowers'],
            ],
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function findBirthdayClients(mixed $query, array $config): Collection
    {
        $daysBefore = $config['days_before'] ?? 1;
        $targetDate = now()->addDays($daysBefore)->format('m-d');

        return $query->whereRaw("DATE_FORMAT(JSON_EXTRACT(vertical_data, '$.birthday'), '%m-%d') = ?", [$targetDate])
            ->get();
    }

    private function findInactiveClients(mixed $query, array $config): Collection
    {
        $daysInactive = $config['days_inactive'] ?? 60;

        return $query->sleeping($daysInactive)->get();
    }

    private function findPostOrderClients(mixed $query, array $config): Collection
    {
        $hoursAfter = $config['hours_after'] ?? 24;
        $from = now()->subHours($hoursAfter + 1);
        $to = now()->subHours($hoursAfter);

        return $query->whereBetween('last_order_at', [$from, $to])->get();
    }

    private function findPostVisitClients(mixed $query, array $config): Collection
    {
        $hoursAfter = $config['hours_after'] ?? 24;
        $from = now()->subHours($hoursAfter + 1);
        $to = now()->subHours($hoursAfter);

        return $query->whereBetween('last_interaction_at', [$from, $to])->get();
    }

    private function findRecentSignups(mixed $query, array $config): Collection
    {
        $hoursAfter = $config['hours_after'] ?? 1;

        return $query->where('created_at', '>=', now()->subHours($hoursAfter))
            ->where('total_orders', 0)
            ->get();
    }

    private function findCustomDateClients(mixed $query, array $config): Collection
    {
        // Базовая реализация — вернуть всех из сегмента
        if (isset($config['segment'])) {
            return $query->bySegment($config['segment'])->get();
        }

        return $query->active()->get();
    }

    private function findAbandonedCartClients(mixed $query, array $config): Collection
    {
        $hoursAgo = $config['hours_ago'] ?? 2;

        return $query->where('last_interaction_at', '<=', now()->subHours($hoursAgo))
            ->whereNotNull('last_interaction_at')
            ->get();
    }

    private function shouldSkipClient(CrmAutomation $automation, CrmClient $client): bool
    {
        // Проверяем, не отправляли ли мы уже эту автоматизацию этому клиенту за последние 24 часа
        $recentLog = CrmAutomationLog::query()
            ->where('crm_automation_id', $automation->id)
            ->where('crm_client_id', $client->id)
            ->where('executed_at', '>=', now()->subHours(24))
            ->exists();

        return $recentLog;
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
