<?php

declare(strict_types=1);

/**
 * SalonController — B2C список салонов Beauty.
 *
 * Публичный endpoint для получения списка салонов.
 * Tenant-scoping происходит через Global Scope на модели.
 *
 * @package CatVRF\Beauty
 * @version 2026.1
 */

namespace App\Domains\Beauty\Presentation\B2C\API\Controllers;

use App\Domains\Beauty\Application\B2C\UseCases\ListSalonsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * SalonController — публичный список салонов для B2C.
 *
 * Constructor injection, correlation_id, structured responses.
 */
final class SalonController extends Controller
{
    public function __construct(
        private ListSalonsUseCase $listSalonsUseCase,
        private LoggerInterface $logger,
    ) {}

    /**
     * Список салонов текущего tenant'а.
     *
     * Tenant определяется из middleware (stancl/tenancy пробрасывает в request).
     * Если tenant не определён — 400.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) ($request->attributes->get('tenant_id', 0));

            if ($tenantId === 0 && $request->user() !== null) {
                $tenantId = (int) ($request->user()->tenant_id ?? 0);
            }

            if ($tenantId === 0) {
                return new JsonResponse([
                    'success'        => false,
                    'message'        => 'Tenant не определён.',
                    'correlation_id' => $correlationId,
                ], 400);
            }

            $salons = ($this->listSalonsUseCase)($tenantId);

            $data = $salons->map(fn ($salon) => $salon->toArray())->all();

            $this->logger->info('Beauty B2C: список салонов', [
                'tenant_id'      => $tenantId,
                'count'          => count($data),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success'        => true,
                'correlation_id' => $correlationId,
                'data'           => $data,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Beauty B2C: ошибка получения салонов', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success'        => false,
                'message'        => 'Не удалось получить список салонов.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
