<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmEventProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * EventsCrmService — CRM-логика для вертикали Мероприятия/Свадьбы.
 *
 * Планирование мероприятий, подрядчики, площадки, чек-листы,
 * важные даты, бюджет, координация поставщиков.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class EventsCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать event-профиль CRM-клиента.
     */
    public function createEventProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $eventStyle = null,
        ?float $typicalBudget = null,
        ?int $typicalGuestCount = null,
        bool $isEventPlanner = false,
        ?string $notes = null
    ): CrmEventProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_event_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $eventStyle,
            $typicalBudget, $typicalGuestCount, $isEventPlanner, $notes
    ): CrmEventProfile {
            $profile = CrmEventProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'event_style' => $eventStyle,
                'typical_budget' => $typicalBudget,
                'typical_guest_count' => $typicalGuestCount,
                'is_event_planner' => $isEventPlanner,
                'upcoming_events' => [],
                'past_events' => [],
                'preferred_venues' => [],
                'preferred_caterers' => [],
                'preferred_decorators' => [],
                'preferred_photographers' => [],
                'vendor_contacts' => [],
                'important_dates' => [],
                'checklist_template' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Events CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'style' => $eventStyle,
                'is_planner' => $isEventPlanner,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_event_profile_created',
                CrmEventProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Запланировать новое мероприятие.
     */
    public function planEvent(
        CrmEventProfile $profile,
        string $eventType,
        string $eventDate,
        string $correlationId,
        ?string $venueName = null,
        ?int $guestCount = null,
        ?float $budget = null,
        ?string $description = null
    ): CrmEventProfile {
        return $this->db->transaction(function () use (
            $profile, $eventType, $eventDate, $correlationId,
            $venueName, $guestCount, $budget, $description
    ): CrmEventProfile {
            $upcoming = $profile->upcoming_events ?? [];
            $eventId = Str::uuid()->toString();

            $upcoming[] = [
                'event_id' => $eventId,
                'type' => $eventType,
                'date' => $eventDate,
                'venue' => $venueName,
                'guest_count' => $guestCount,
                'budget' => $budget,
                'description' => $description,
                'status' => 'planning',
                'checklist' => $this->getDefaultChecklist($eventType),
                'created_at' => now()->toDateString(),
            ];

            $profile->update(['upcoming_events' => $upcoming]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $dates = $profile->important_dates ?? [];
            $dates[] = [
                'event_id' => $eventId,
                'type' => $eventType,
                'date' => $eventDate,
                'label' => "{$eventType} — {$eventDate}",
            ];
            $profile->update(['important_dates' => $dates]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $profile->crm_client_id,
                    tenantId: $profile->tenant_id,
                    correlationId: $correlationId,
                    type: 'note',
                    channel: 'app',
                    direction: 'inbound',
                    content: "Запланировано мероприятие: {$eventType} на {$eventDate}",
                    metadata: [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'budget' => $budget,
                        'guest_count' => $guestCount,
                    ]
    )
    );

            $this->audit->log(
                'crm_event_planned',
                CrmEventProfile::class,
                $profile->id,
                [],
                ['event_type' => $eventType, 'date' => $eventDate],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Обновить статус пункта чек-листа мероприятия.
     */
    public function updateChecklistItem(
        CrmEventProfile $profile,
        string $eventId,
        string $checklistItem,
        bool $completed,
        string $correlationId
    ): CrmEventProfile {
        return $this->db->transaction(function () use ($profile, $eventId, $checklistItem, $completed, $correlationId): CrmEventProfile {
            $upcoming = $profile->upcoming_events ?? [];

            foreach ($upcoming as &$event) {
                if (($event['event_id'] ?? '') === $eventId) {
                    $checklist = $event['checklist'] ?? [];
                    foreach ($checklist as &$item) {
                        if (($item['task'] ?? '') === $checklistItem) {
                            $item['completed'] = $completed;
                            $item['completed_at'] = $completed ? now()->toDateString() : null;
                            break;
                        }
                    }
                    unset($item);
                    $event['checklist'] = $checklist;
                    break;
                }
            }
            unset($event);

            $profile->update(['upcoming_events' => $upcoming]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Завершить мероприятие (переместить в past_events).
     */
    public function completeEvent(
        CrmEventProfile $profile,
        string $eventId,
        string $correlationId,
        ?int $rating = null,
        ?string $feedback = null
    ): CrmEventProfile {
        return $this->db->transaction(function () use ($profile, $eventId, $correlationId, $rating, $feedback): CrmEventProfile {
            $upcoming = $profile->upcoming_events ?? [];
            $past = $profile->past_events ?? [];
            $newUpcoming = [];

            foreach ($upcoming as $event) {
                if (($event['event_id'] ?? '') === $eventId) {
                    $event['status'] = 'completed';
                    $event['rating'] = $rating;
                    $event['feedback'] = $feedback;
                    $event['completed_at'] = now()->toDateString();
                    $past[] = $event;
                } else {
                    $newUpcoming[] = $event;
                }
            }

            $profile->update([
                'upcoming_events' => $newUpcoming,
                'past_events' => $past,
            ]);

            $this->logger->info('Event completed', [
                'profile_id' => $profile->id,
                'event_id' => $eventId,
                'rating' => $rating,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Добавить предпочтительного подрядчика.
     */
    public function addPreferredVendor(
        CrmEventProfile $profile,
        string $vendorType,
        string $vendorName,
        string $correlationId,
        ?string $contactInfo = null,
        ?int $rating = null
    ): CrmEventProfile {
        return $this->db->transaction(function () use ($profile, $vendorType, $vendorName, $correlationId, $contactInfo, $rating): CrmEventProfile {
            $field = match ($vendorType) {
                'venue' => 'preferred_venues',
                'caterer' => 'preferred_caterers',
                'decorator' => 'preferred_decorators',
                'photographer' => 'preferred_photographers',
                default => 'vendor_contacts',
            };

            $list = $profile->{$field} ?? [];
            $list[] = [
                'name' => $vendorName,
                'contact' => $contactInfo,
                'rating' => $rating,
                'type' => $vendorType,
                'added_at' => now()->toDateString(),
            ];

            $profile->update([$field => $list]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Клиенты с предстоящими мероприятиями.
     */
    public function getUpcomingEvents(int $tenantId, int $daysAhead = 30): Collection
    {
        return CrmEventProfile::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->get()
            ->filter(function (CrmEventProfile $profile) use ($daysAhead): bool {
                foreach ($profile->upcoming_events ?? [] as $event) {
                    if (isset($event['date'])) {
                        $eventDate = \Carbon\Carbon::parse($event['date']);
                        if ($eventDate->isBetween(now(), now()->addDays($daysAhead))) {
                            return true;
                        }
                    }
                }
                return false;
            });
    }

    /**
     * Дефолтный чек-лист по типу мероприятия.
     */
    private function getDefaultChecklist(string $eventType): array
    {
        $common = [
            ['task' => 'Выбрать площадку', 'completed' => false],
            ['task' => 'Определить бюджет', 'completed' => false],
            ['task' => 'Составить список гостей', 'completed' => false],
            ['task' => 'Заказать кейтеринг', 'completed' => false],
            ['task' => 'Найти фотографа', 'completed' => false],
        ];

        $extra = match ($eventType) {
            'wedding' => [
                ['task' => 'Выбрать платье/костюм', 'completed' => false],
                ['task' => 'Заказать торт', 'completed' => false],
                ['task' => 'Организовать церемонию', 'completed' => false],
                ['task' => 'Заказать цветы', 'completed' => false],
                ['task' => 'Забронировать тамаду/ведущего', 'completed' => false],
            ],
            'corporate' => [
                ['task' => 'Подготовить презентацию', 'completed' => false],
                ['task' => 'Заказать оборудование (экран, звук)', 'completed' => false],
                ['task' => 'Организовать трансфер', 'completed' => false],
            ],
            'birthday' => [
                ['task' => 'Заказать торт', 'completed' => false],
                ['task' => 'Купить подарок', 'completed' => false],
                ['task' => 'Заказать аниматора', 'completed' => false],
            ],
            default => [],
        };

        return array_merge($common, $extra);
    }

    /**
     * «Спящие» event-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 90): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('events')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
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
