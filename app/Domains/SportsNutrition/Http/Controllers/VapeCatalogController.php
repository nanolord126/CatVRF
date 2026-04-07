<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VapeCatalogController extends Controller
{

    /**
         * Конструктор с DP.
         */
        public function __construct(
            private VapeCatalogService $catalogService, private readonly LoggerInterface $logger) {}

        /**
         * Список всех брендов вейпов текущего теннанта.
         */
        public function indexBrands(): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $brands = VapeBrand::orderBy('name')->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'brands' => $brands,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->error('Vape catalog controller: brands index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
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
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $devices = VapeDevice::with('brand')->orderBy('name')->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'devices' => $devices,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->error('Vape catalog controller: devices index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
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
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $liquids = VapeLiquid::with('brand')->orderBy('name')->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'liquids' => $liquids,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->error('Vape catalog controller: liquids index failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
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
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $device = VapeDevice::where('uuid', $uuid)->with('brand')->firstOrFail();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'device' => $device,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->warning('Vape device not found', [
                    'device_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Device not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
}
