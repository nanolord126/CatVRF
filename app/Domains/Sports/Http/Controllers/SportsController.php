<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sports\SportsMembership;
use App\Models\Sports\SportsAttendance;
use App\Domains\Sports\Services\SportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SportsController extends Controller
{
    use AuthorizesRequests;

    private SportsService $sportsService;
    private string $correlationId;

    public function __construct(SportsService $sportsService)
    {
        $this->sportsService = $sportsService;
        $this->correlationId = request()->header('X-Correlation-ID') ?? Str::uuid();
        $this->middleware('auth:sanctum');
        $this->middleware('tenant');
    }

    /**
     * Получить все спортивные членства в системе
     * GET /api/sports/memberships
     */
    public function indexMemberships(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', SportsMembership::class);

            $query = SportsMembership::query()
                ->where('tenant_id', tenant_id())
                ->with(['user', 'sport']);

            // Фильтрация по статусу
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Фильтрация по виду спорта
            if ($request->filled('sport_id')) {
                $query->where('sport_id', $request->input('sport_id'));
            }

            // Фильтрация по пользователю
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            $memberships = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            Log::info('Sports: Listed memberships', [
                'count' => $memberships->count(),
                'total' => $memberships->total(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $memberships->items(),
                'pagination' => [
                    'total' => $memberships->total(),
                    'per_page' => $memberships->perPage(),
                    'current_page' => $memberships->currentPage(),
                    'last_page' => $memberships->lastPage(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to list memberships', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении членств',
            ], 400);
        }
    }

    /**
     * Получить детали конкретного членства
     * GET /api/sports/memberships/{id}
     */
    public function showMembership(SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('view', $membership);

            Log::info('Sports: Viewed membership', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $membership->load(['user', 'sport', 'attendances']),
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to view membership', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Членство не найдено или недоступно',
            ], 404);
        }
    }

    /**
     * Создать новое спортивное членство
     * POST /api/sports/memberships
     */
    public function storeMembership(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', SportsMembership::class);

            $validated = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'user_id' => 'required|exists:users,id',
                'membership_type' => 'required|in:monthly,quarterly,annual,lifetime',
                'started_at' => 'required|date',
            ]);

            $validated['tenant_id'] = tenant_id();
            $validated['correlation_id'] = $this->correlationId;

            $membership = $this->sportsService->createMembership($validated);

            Log::info('Sports: Created membership', [
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $membership->load(['user', 'sport']),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to create membership', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании членства',
            ], 422);
        }
    }

    /**
     * Обновить членство
     * PATCH /api/sports/memberships/{id}
     */
    public function updateMembership(Request $request, SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('update', $membership);

            $validated = $request->validate([
                'membership_type' => 'nullable|in:monthly,quarterly,annual,lifetime',
                'started_at' => 'nullable|date',
            ]);

            if (!empty($validated)) {
                $validated['correlation_id'] = $this->correlationId;
                $membership = $this->sportsService->updateMembership($membership, $validated);
            }

            Log::info('Sports: Updated membership', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $membership->load(['user', 'sport']),
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to update membership', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении членства',
            ], 422);
        }
    }

    /**
     * Удалить членство
     * DELETE /api/sports/memberships/{id}
     */
    public function destroyMembership(SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('delete', $membership);

            $membership->delete();

            Log::info('Sports: Deleted membership', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Членство успешно удалено',
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to delete membership', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении членства',
            ], 422);
        }
    }

    /**
     * Продлить членство
     * POST /api/sports/memberships/{id}/renew
     */
    public function renewMembership(Request $request, SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('renew', $membership);

            $validated = $request->validate([
                'membership_type' => 'required|in:monthly,quarterly,annual,lifetime',
                'started_at' => 'required|date',
            ]);

            $validated['correlation_id'] = $this->correlationId;

            $membership = $this->sportsService->renewMembership($membership, $validated);

            Log::info('Sports: Renewed membership', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $membership->load(['user', 'sport']),
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to renew membership', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при продлении членства',
            ], 422);
        }
    }

    /**
     * Отменить членство
     * POST /api/sports/memberships/{id}/cancel
     */
    public function cancelMembership(Request $request, SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('cancel', $membership);

            $validated = $request->validate([
                'reason' => 'required|string|min:10',
            ]);

            $validated['correlation_id'] = $this->correlationId;

            $membership = $this->sportsService->cancelMembership($membership, $validated['reason']);

            Log::info('Sports: Cancelled membership', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $membership->load(['user', 'sport']),
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to cancel membership', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене членства',
            ], 422);
        }
    }

    /**
     * Записать посещение тренировки
     * POST /api/sports/memberships/{id}/attendance
     */
    public function recordAttendance(Request $request, SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('view', $membership);

            $validated = $request->validate([
                'attended_at' => 'required|date',
                'duration_minutes' => 'nullable|integer|min:15|max:480',
                'notes' => 'nullable|string|max:1000',
            ]);

            $validated['membership_id'] = $membership->id;
            $validated['correlation_id'] = $this->correlationId;

            $attendance = $this->sportsService->recordAttendance($validated);

            Log::info('Sports: Recorded attendance', [
                'attendance_id' => $attendance->id,
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendance,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to record attendance', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при записи посещения',
            ], 422);
        }
    }

    /**
     * Получить статистику спортивного членства
     * GET /api/sports/memberships/{id}/statistics
     */
    public function getMembershipStatistics(SportsMembership $membership): JsonResponse
    {
        try {
            $this->authorize('view', $membership);

            $statistics = $this->sportsService->getStatistics($membership->id);

            Log::info('Sports: Retrieved membership statistics', [
                'membership_id' => $membership->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (Throwable $e) {
            Log::error('Sports: Failed to get membership statistics', [
                'membership_id' => $membership->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики',
            ], 400);
        }
    }
}
