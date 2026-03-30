<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalonController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Список салонов (GET /beauty/salons).
         */
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $salons = BeautySalon::query()
                    ->where('is_active', true)
                    ->with(['masters', 'services'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $salons,
                    'correlation_id' => $correlationId
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('API Error: List Salons Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось загрузить список салонов.',
                    'correlation_id' => $correlationId
                ], 500);
            }
        }

        /**
         * Создать новый салон (POST /beauty/salons).
         */
        public function store(CreateBeautySalonRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $salon = DB::transaction(function () use ($request, $correlationId) {
                    return BeautySalon::create(array_merge($request->validated(), [
                        'uuid' => (string) Str::uuid(),
                        'tenant_id' => tenant('id') ?? 1,
                        'correlation_id' => $correlationId,
                        'is_active' => true
                    ]));
                });

                Log::channel('audit')->info('API Success: Beauty Salon Created', [
                    'salon_id' => $salon->id,
                    'correlation_id' => $correlationId
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $salon,
                    'correlation_id' => $correlationId
                ], 201);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('API Error: Create Salon Failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при создании салона.',
                    'correlation_id' => $correlationId
                ], 400);
            }
        }

        /**
         * Информация о салоне (GET /beauty/salons/{salon}).
         */
        public function show(BeautySalon $salon): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            return response()->json([
                'success' => true,
                'data' => $salon->load(['masters', 'services', 'reviews']),
                'correlation_id' => $correlationId
            ]);
        }
}
