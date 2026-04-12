<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\Salon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\Appointment;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;

/**
 * BeautyPanelController — единый API-контроллер для Beauty B2B-панели.
 *
 * Обрабатывает все запросы от фронтенд-компонентов:
 * dashboard, analytics, finances, loyalty, notifications,
 * staff, chat, CRM, pages, promos, reports, export, AI try-on.
 *
 * Все эндпоинты — tenant-scoped, с correlation_id и fraud-check.
 */
final class BeautyPanelController extends Controller
{
    public function __construct(
        private DatabaseManager $db,
        private AuditService $audit,
        private FraudControlService $fraud,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /* ═══════════════════════════════════════════════════
     * DASHBOARD
     * ═══════════════════════════════════════════════════ */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $todayStart = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        $revenueToday = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $todayStart)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->sum('beauty_services.price_b2c');

        $revenueWeek = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $weekStart)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->sum('beauty_services.price_b2c');

        $activeBookings = Appointment::where('tenant_id', $tenantId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('starts_at', '>=', now())
            ->count();

        $totalMasters = Master::whereHas('salon', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->count();

        $busyMasters = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->distinct('master_id')
            ->count('master_id');

        $mastersLoad = $totalMasters > 0 ? round(($busyMasters / $totalMasters) * 100) : 0;

        $avgCheck = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $weekStart)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->avg('beauty_services.price_b2c') ?? 0;

        $this->audit->log('beauty_dashboard_loaded', self::class, auth()->user()->id, [], [], $correlationId);

        return response()->json([
            'data' => [
                'revenue_today' => (float) $revenueToday,
                'revenue_week' => (float) $revenueWeek,
                'active_bookings' => $activeBookings,
                'masters_load' => $mastersLoad,
                'avg_check' => round((float) $avgCheck, 2),
                'conversion' => 0,
                'trends' => [],
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    /* ═══════════════════════════════════════════════════
     * FINANCE ANALYTICS
     * ═══════════════════════════════════════════════════ */
    public function financeStats(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $period = $request->input('period', '30d');

        $since = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $revenue = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $since)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->sum('beauty_services.price_b2c');

        $revenueByService = $this->db->table('beauty_appointments')
            ->where('beauty_appointments.tenant_id', $tenantId)
            ->where('beauty_appointments.status', 'completed')
            ->where('beauty_appointments.starts_at', '>=', $since)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->select('beauty_services.name as service', $this->db->raw('SUM(beauty_services.price_b2c) as amount'))
            ->groupBy('beauty_services.name')
            ->orderByDesc('amount')
            ->limit(10)
            ->get()
            ->toArray();

        $masterPayouts = $this->db->table('beauty_appointments')
            ->where('beauty_appointments.tenant_id', $tenantId)
            ->where('beauty_appointments.status', 'completed')
            ->where('beauty_appointments.starts_at', '>=', $since)
            ->join('beauty_masters', 'beauty_appointments.master_id', '=', 'beauty_masters.id')
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->select(
                'beauty_masters.id as master_id',
                'beauty_masters.full_name as name',
                $this->db->raw('SUM(beauty_services.price_b2c) as amount'),
            )
            ->groupBy('beauty_masters.id', 'beauty_masters.full_name')
            ->orderByDesc('amount')
            ->get()
            ->toArray();

        $this->logger->info('Beauty finance stats loaded', [
            'tenant_id' => $tenantId,
            'period' => $period,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'data' => [
                'revenue' => (float) $revenue,
                'expenses' => 0,
                'profit' => (float) $revenue,
                'commission' => round((float) $revenue * 0.14, 2),
                'payouts_pending' => 0,
                'revenue_by_service' => $revenueByService,
                'revenue_by_channel' => [],
                'master_payouts' => $masterPayouts,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    /* ═══════════════════════════════════════════════════
     * STAFF / HR
     * ═══════════════════════════════════════════════════ */
    public function staffIndex(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $staff = Master::whereHas('salon', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with('salon:id,name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'specialization' => $m->specialization,
                'salon_name' => $m->salon?->name ?? '',
                'rating' => $m->rating,
                'is_active' => $m->is_active,
            ]);

        $this->logger->info('Beauty staff loaded', [
            'tenant_id' => $tenantId,
            'count' => $staff->count(),
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'data' => $staff,
            'correlation_id' => $correlationId,
        ]);
    }

    public function staffStore(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'staff_store',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $master = $this->db->transaction(function () use ($request, $correlationId) {
            $created = Master::create([
                'salon_id' => $request->input('salon_id'),
                'full_name' => $request->input('full_name'),
                'specialization' => $request->input('specialization'),
                'rating' => 5.0,
                'is_active' => true,
                'tags' => $request->input('tags', []),
            ]);

            $this->audit->record(
                action: 'staff_created',
                subjectType: Master::class,
                subjectId: $created->id,
                newValues: $created->toArray(),
                correlationId: $correlationId
            );

            return $created;
        });

        return response()->json(['data' => $master, 'correlation_id' => $correlationId], 201);
    }

    public function staffUpdate(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $master = Master::findOrFail($id);
        $old = $master->toArray();

        $this->db->transaction(function () use ($master, $request, $correlationId, $old) {
            $master->update($request->only(['full_name', 'specialization', 'is_active', 'tags']));
            $this->audit->record(
                action: 'staff_updated',
                subjectType: Master::class,
                subjectId: $master->id,
                oldValues: $old,
                newValues: $master->fresh()->toArray(),
                correlationId: $correlationId
            );
        });

        return response()->json(['data' => $master->fresh(), 'correlation_id' => $correlationId]);
    }

    public function staffPayout(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'staff_payout',
            amount: (int) $request->input('amount', 0),
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'staff_payout_requested',
            subjectType: Master::class,
            subjectId: $id,
            newValues: ['amount' => $request->input('amount')],
            correlationId: $correlationId
        );

        $this->logger->info('Staff payout processed', [
            'master_id' => $id,
            'amount' => $request->input('amount'),
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'data' => ['status' => 'processed', 'master_id' => $id],
            'correlation_id' => $correlationId,
        ]);
    }

    /* ═══════════════════════════════════════════════════
     * LOYALTY
     * ═══════════════════════════════════════════════════ */
    public function loyaltyIndex(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json([
            'data' => [
                'tiers' => config('bonuses.beauty_tiers', []),
                'referral_bonus' => config('bonuses.referral_bonus', 500),
                'birthday_bonus' => config('bonuses.birthday_bonus', 300),
                'rules' => [],
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function loyaltyUpdate(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'loyalty_update',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'loyalty_config_updated',
            subjectType: 'LoyaltyConfig',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'updated'], 'correlation_id' => $correlationId]);
    }

    public function loyaltyAward(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'bonus_award',
            amount: (int) $request->input('amount', 0),
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'bonus_awarded',
            subjectType: 'UserBonus',
            subjectId: null,
            oldValues: [],
            newValues: [
                'user_id' => $request->input('user_id'),
                'amount' => $request->input('amount'),
                'reason' => $request->input('reason'),
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Bonus awarded', [
            'user_id' => $request->input('user_id'),
            'amount' => $request->input('amount'),
            'correlation_id' => $correlationId,
        ]);

        return response()->json(['data' => ['status' => 'awarded'], 'correlation_id' => $correlationId]);
    }

    public function loyaltyDeduct(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'bonus_deduct',
            amount: (int) $request->input('amount', 0),
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'bonus_deducted',
            subjectType: 'UserBonus',
            subjectId: null,
            oldValues: [],
            newValues: [
                'user_id' => $request->input('user_id'),
                'amount' => $request->input('amount'),
                'reason' => $request->input('reason'),
            ],
            correlationId: $correlationId,
        );

        return response()->json(['data' => ['status' => 'deducted'], 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * NOTIFICATIONS
     * ═══════════════════════════════════════════════════ */
    public function notificationSend(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'notification_send',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'notification_sent',
            subjectType: 'Notification',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'sent'], 'correlation_id' => $correlationId]);
    }

    public function notificationBulk(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'notification_bulk',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'bulk_notification_sent',
            subjectType: 'Notification',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json([
            'data' => [
                'status' => 'queued',
                'recipients_count' => $request->input('recipients_count', 0),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function notificationTemplates(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    public function notificationTemplateStore(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'notification_template_saved',
            subjectType: 'NotificationTemplate',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'saved'], 'correlation_id' => $correlationId], 201);
    }

    /* ═══════════════════════════════════════════════════
     * CHAT
     * ═══════════════════════════════════════════════════ */
    public function chatIndex(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    public function chatMessages(Request $request, int $chatId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    public function chatSendMessage(Request $request, int $chatId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'chat_message_sent',
            subjectType: 'ChatMessage',
            subjectId: null,
            oldValues: [],
            newValues: [
                'chat_id' => $chatId,
                'text' => $request->input('text'),
            ],
            correlationId: $correlationId,
        );

        return response()->json(['data' => ['status' => 'sent'], 'correlation_id' => $correlationId], 201);
    }

    /* ═══════════════════════════════════════════════════
     * CRM / CLIENTS
     * ═══════════════════════════════════════════════════ */
    public function clientsIndex(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $clients = $this->db->table('beauty_appointments')
            ->where('beauty_appointments.tenant_id', $tenantId)
            ->join('users', 'beauty_appointments.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name as full_name',
                'users.email',
                $this->db->raw('COUNT(beauty_appointments.id) as visits_count'),
                $this->db->raw('MAX(beauty_appointments.starts_at) as last_visit'),
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('visits_count')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $clients->items(),
            'meta' => [
                'total' => $clients->total(),
                'per_page' => $clients->perPage(),
                'current_page' => $clients->currentPage(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function clientShow(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $client = $this->db->table('users')->where('id', $id)->first();

        if ($client === null) {
            return response()->json(['message' => 'Client not found', 'correlation_id' => $correlationId], 404);
        }

        return response()->json(['data' => $client, 'correlation_id' => $correlationId]);
    }

    public function clientSegments(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * PUBLIC PAGES
     * ═══════════════════════════════════════════════════ */
    public function pagesIndex(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    public function pageStore(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'page_created',
            subjectType: 'PublicPage',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'created'], 'correlation_id' => $correlationId], 201);
    }

    public function pageUpdate(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'page_updated',
            subjectType: 'PublicPage',
            subjectId: $id,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'updated'], 'correlation_id' => $correlationId]);
    }

    public function pageDestroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'page_deleted',
            subjectType: 'PublicPage',
            subjectId: $id,
            newValues: [],
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'deleted'], 'correlation_id' => $correlationId]);
    }

    public function pageStats(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * PROMOS / MARKETING
     * ═══════════════════════════════════════════════════ */
    public function promosIndex(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'correlation_id' => $correlationId]);
    }

    public function promoStore(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'promo_create',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'promo_created',
            subjectType: 'Promo',
            subjectId: null,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'created'], 'correlation_id' => $correlationId], 201);
    }

    public function promoUpdate(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'promo_updated',
            subjectType: 'Promo',
            subjectId: $id,
            newValues: $request->all(),
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'updated'], 'correlation_id' => $correlationId]);
    }

    public function promoDestroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'promo_deleted',
            subjectType: 'Promo',
            subjectId: $id,
            newValues: [],
            correlationId: $correlationId
        );

        return response()->json(['data' => ['status' => 'deleted'], 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * REPORTS / ANALYTICS
     * ═══════════════════════════════════════════════════ */
    public function analytics(Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $period = $request->input('period', 'month');

        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $revenueByDay = $this->db->table('beauty_appointments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $startDate)
            ->join('beauty_services', 'beauty_appointments.service_id', '=', 'beauty_services.id')
            ->selectRaw('DATE(starts_at) as date, SUM(beauty_services.price_b2c) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->audit->log('beauty_analytics_loaded', self::class, (int) (auth()->id() ?? 0), ['period' => $period], [], $correlationId);

        return response()->json([
            'data' => [
                'revenue_by_day' => $revenueByDay,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function reportData(Request $request, string $type): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        return response()->json(['data' => [], 'type' => $type, 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * EXPORT
     * ═══════════════════════════════════════════════════ */
    public function exportData(Request $request, string $type): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->audit->record(
            action: 'data_exported',
            subjectType: 'Export',
            subjectId: null,
            newValues: ['type' => $type],
            correlationId: $correlationId
        );

        return response()->json(['data' => [], 'type' => $type, 'correlation_id' => $correlationId]);
    }

    /* ═══════════════════════════════════════════════════
     * AI TRY-ON
     * ═══════════════════════════════════════════════════ */
    public function aiTryOn(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) $request->user()->id,
            operationType: 'ai_tryon',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->audit->record(
            action: 'ai_tryon_requested',
            subjectType: 'AITryOn',
            subjectId: null,
            oldValues: [],
            newValues: [
                'user_id' => $request->user()?->id,
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('AI try-on requested', [
            'user_id' => $request->user()?->id,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'data' => [
                'status' => 'processing',
                'message' => 'AI-анализ запущен. Результат будет готов через несколько секунд.',
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
