<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\Dish;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления блюдами.
 * Production 2026.
 */
final class DishController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $dishes = Dish::query()
                ->where('tenant_id', tenant('id'))
                ->where('is_available', true)
                ->select(['id', 'name', 'description', 'price', 'calories', 'allergens', 'image_url', 'rating'])
                ->paginate(30);

            return response()->json([
                'success' => true,
                'data' => $dishes,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(Dish $dish): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $dish,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Блюдо не найдено'], 404);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'dish_create', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $data = request()->validate([
                'menu_id'           => 'required|integer',
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'price'             => 'required|integer|min:1',
                'calories'          => 'nullable|integer',
                'allergens'         => 'nullable|array',
                'cooking_time_minutes' => 'nullable|integer',
                'consumables_json'  => 'nullable|array',
                'is_available'      => 'boolean',
            ]);

            $dish = $this->db->transaction(function () use ($data, $correlationId) {
                return Dish::create([
                    ...$data,
                    'tenant_id'      => tenant('id'),
                    'correlation_id' => $correlationId,
                    'uuid'           => Str::uuid(),
                ]);
            });

            $this->log->channel('audit')->info('Dish created', [
                'correlation_id' => $correlationId,
                'dish_id'   => $dish->id,
                'tenant_id' => $dish->tenant_id,
                'user_id'   => auth()->id(),
                'name'      => $dish->name,
            ]);

            return response()->json([
                'success'        => true,
                'data'           => $dish,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Dish create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка создания блюда.', 'correlation_id' => $correlationId], 500);
        }
    }

    public function update(Dish $dish): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'dish_update', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $data = request()->validate([
                'name'        => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'price'       => 'nullable|integer|min:1',
                'calories'    => 'nullable|integer',
                'allergens'   => 'nullable|array',
                'is_available' => 'nullable|boolean',
            ]);

            $before = $dish->getAttributes();

            $this->db->transaction(function () use ($dish, $data) {
                $dish->update($data);
            });

            $this->log->channel('audit')->info('Dish updated', [
                'correlation_id' => $correlationId,
                'dish_id'   => $dish->id,
                'tenant_id' => $dish->tenant_id,
                'user_id'   => auth()->id(),
                'before'    => $before,
                'after'     => $data,
            ]);

            return response()->json([
                'success'        => true,
                'data'           => $dish->fresh(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Dish update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка обновления блюда.', 'correlation_id' => $correlationId], 500);
        }
    }

    public function destroy(Dish $dish): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'dish_delete', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $this->db->transaction(function () use ($dish) {
                $dish->delete();
            });

            $this->log->channel('audit')->info('Dish deleted', [
                'correlation_id' => $correlationId,
                'dish_id'   => $dish->id,
                'tenant_id' => $dish->tenant_id,
                'user_id'   => auth()->id(),
            ]);

            return response()->json([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Dish delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка удаления блюда.', 'correlation_id' => $correlationId], 500);
        }
    }
}
