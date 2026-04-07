<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class DishController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $dishes = Dish::query()
                    ->where('tenant_id', tenant()->id)
                    ->where('is_available', true)
                    ->select(['id', 'name', 'description', 'price', 'calories', 'allergens', 'image_url', 'rating'])
                    ->paginate(30);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $dishes,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(Dish $dish): JsonResponse
        {
            try {
                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $dish,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Блюдо не найдено'], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'dish_create', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $data = $request->validate([
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
                        'tenant_id'      => tenant()->id,
                        'correlation_id' => $correlationId,
                        'uuid'           => Str::uuid(),
                    ]);
                });

                $this->logger->info('Dish created', [
                    'correlation_id' => $correlationId,
                    'dish_id'   => $dish->id,
                    'tenant_id' => $dish->tenant_id,
                    'user_id'   => $request->user()?->id,
                    'name'      => $dish->name,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'data'           => $dish,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Dish create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка создания блюда.', 'correlation_id' => $correlationId], 500);
            }
        }

        public function update(Dish $dish): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'dish_update', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $data = $request->validate([
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

                $this->logger->info('Dish updated', [
                    'correlation_id' => $correlationId,
                    'dish_id'   => $dish->id,
                    'tenant_id' => $dish->tenant_id,
                    'user_id'   => $request->user()?->id,
                    'before'    => $before,
                    'after'     => $data,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'data'           => $dish->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Dish update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка обновления блюда.', 'correlation_id' => $correlationId], 500);
            }
        }

        public function destroy(Dish $dish): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'dish_delete', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $this->db->transaction(function () use ($dish) {
                    $dish->delete();
                });

                $this->logger->info('Dish deleted', [
                    'correlation_id' => $correlationId,
                    'dish_id'   => $dish->id,
                    'tenant_id' => $dish->tenant_id,
                    'user_id'   => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Dish delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка удаления блюда.', 'correlation_id' => $correlationId], 500);
            }
        }
}
