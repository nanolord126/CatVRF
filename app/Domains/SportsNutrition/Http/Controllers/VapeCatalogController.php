<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeCatalogController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с DP.
         */
        public function __construct(
            private VapeCatalogService $catalogService,
        ) {}

        /**
         * Список всех брендов вейпов текущего теннанта.
         */
        public function indexBrands(): JsonResponse
        {
            $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $brands = VapeBrand::orderBy('name')->get();

                return response()->json([
                    'success' => true,
                    'brands' => $brands,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                Log::channel('audit')->error('Vape catalog controller: brands index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Internal error fetching brands',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Список всех устройств текущего теннанта.
         */
        public function indexDevices(): JsonResponse
        {
            $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $devices = VapeDevice::with('brand')->orderBy('name')->get();

                return response()->json([
                    'success' => true,
                    'devices' => $devices,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                Log::channel('audit')->error('Vape catalog controller: devices index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Internal error fetching devices',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Список всех жидкостей текущего теннанта.
         */
        public function indexLiquids(): JsonResponse
        {
            $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $liquids = VapeLiquid::with('brand')->orderBy('name')->get();

                return response()->json([
                    'success' => true,
                    'liquids' => $liquids,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                Log::channel('audit')->error('Vape catalog controller: liquids index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Internal error fetching liquids',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Информация о конкретном устройстве.
         */
        public function showDevice(string $uuid): JsonResponse
        {
            $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $device = VapeDevice::where('uuid', $uuid)->with('brand')->firstOrFail();

                return response()->json([
                    'success' => true,
                    'device' => $device,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                Log::channel('audit')->warning('Vape device not found', [
                    'device_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
}
