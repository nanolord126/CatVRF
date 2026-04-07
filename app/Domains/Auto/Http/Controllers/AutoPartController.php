<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class AutoPartController extends Controller
{

    public function __construct(private readonly AutoPartsInventoryService $inventoryService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $parts = AutoPart::query()
                    ->where('tenant_id', tenant()->id)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $parts,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при получении запчастей',
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'auto_part_creation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $this->authorize('create', AutoPart::class);

                $request->validate([
                    'sku' => 'required|unique:auto_parts',
                    'name' => 'required|string',
                    'brand' => 'required|string',
                    'price' => 'required|integer',
                    'current_stock' => 'required|integer',
                    'min_stock_threshold' => 'required|integer',
                ]);

                $validated = $request->all();
                $part = $this->db->transaction(function () use ($validated) {
                    return AutoPart::create([
                        'tenant_id' => tenant()->id,
                        'sku' => ($validated['sku'] ?? null),
                        'name' => ($validated['name'] ?? null),
                        'brand' => ($validated['brand'] ?? null),
                        'price' => ($validated['price'] ?? null),
                        'current_stock' => ($validated['current_stock'] ?? null),
                        'min_stock_threshold' => ($validated['min_stock_threshold'] ?? null),
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $part,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при создании запчасти',
                ], 500);
            }
        }

        public function show(AutoPart $part): JsonResponse
        {
            $this->authorize('view', $part);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $part,
            ]);
        }

        public function update(Request $request, AutoPart $part): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'auto_part_update', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $this->authorize('update', $part);

                $request->validate([
                    'price' => 'nullable|integer',
                    'min_stock_threshold' => 'nullable|integer',
                ]);

                $part->update($request->only(['price', 'min_stock_threshold']));

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $part,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при обновлении запчасти',
                ], 500);
            }
        }

        public function restock(Request $request, AutoPart $part): JsonResponse
        {
            try {
                $this->authorize('update', $part);

                $request->validate([
                    'quantity' => 'required|integer|min:1',
                    'reason' => 'nullable|string',
                ]);

                $this->inventoryService->addStock(
                    $part->id,
                    $request->get('quantity'),
                    $request->get('reason', 'Пополнение остатка'),
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Остаток пополнен',
                    'data' => $part->fresh(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при пополнении остатка',
                ], 500);
            }
        }

        public function lowStock(Request $request): JsonResponse
        {
            $parts = AutoPart::query()
                ->where('tenant_id', tenant()->id)
                ->whereRaw('current_stock < min_stock_threshold')
                ->get();

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $parts,
                'count' => $parts->count(),
            ]);
        }

        public function delete(AutoPart $part): JsonResponse
        {
            try {
                $this->authorize('delete', $part);

                $part->delete();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Запчасть удалена',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при удалении запчасти',
                ], 500);
            }
        }
}
