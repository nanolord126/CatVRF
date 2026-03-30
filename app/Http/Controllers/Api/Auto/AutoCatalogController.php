<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoCatalogController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private AutoPartService $partService
        ) {}
        /**
         * Поиск совместимых запчастей по VIN.
         *
         * GET /api/v1/auto/catalog/search?vin={vin}
         */
        public function searchByVin(AutoVinSearchRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $vin = $request->get('vin');
            try {
                Log::channel('audit')->info('VIN Search Request Received', [
                    'vin' => $vin,
                    'correlation_id' => $correlationId,
                    'user_agent' => $request->userAgent(),
                ]);
                // 1. Поиск в каталоге (Service Layer)
                $parts = $this->partService->findPartsByVin($vin, $correlationId);
                return response()->json([
                    'success' => true,
                    'data' => $parts,
                    'meta' => [
                        'vin' => $vin,
                        'correlation_id' => $correlationId,
                        'count' => $parts->count(),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('VIN Search Failed', [
                    'vin' => $vin,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка при поиске запчастей. Попробуйте позже.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
