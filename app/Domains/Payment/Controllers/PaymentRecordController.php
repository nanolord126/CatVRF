<?php

declare(strict_types=1);

namespace App\Domains\Payment\Controllers;

use App\Domains\Payment\DTOs\CreatePaymentRecordDto;
use App\Domains\Payment\DTOs\UpdatePaymentRecordDto;
use App\Domains\Payment\Resources\PaymentRecordResource;
use App\Domains\Payment\Services\PaymentService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;

/**
 * REST API контроллер для платёжных записей.
 *
 * Тонкий контроллер — вся логика делегирована в PaymentService.
 */
final class PaymentRecordController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /api/payments/{id}
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $record = $this->paymentService->findById($id);

        if ($record === null) {
            return $this->response->json([
                'success' => false,
                'error' => 'Payment record not found',
                'correlation_id' => $this->extractCorrelationId($request),
            ], 404);
        }

        return $this->response->json([
            'success' => true,
            'data' => new PaymentRecordResource($record),
            'meta' => [
                'correlation_id' => $this->extractCorrelationId($request),
            ],
        ]);
    }

    /**
     * POST /api/payments
     */
    public function store(Request $request): JsonResponse
    {
        $dto = CreatePaymentRecordDto::from($request);
        $record = $this->paymentService->create($dto);

        $this->logger->info('Payment record created via API', [
            'payment_record_id' => $record->id,
            'correlation_id' => $dto->correlationId,
        ]);

        return $this->response->json([
            'success' => true,
            'data' => new PaymentRecordResource($record),
            'meta' => [
                'correlation_id' => $dto->correlationId,
            ],
        ], 201);
    }

    /**
     * PATCH /api/payments/{id}/status
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $dto = new UpdatePaymentRecordDto(
            paymentRecordId: $id,
            status: (string) $request->input('status', ''),
            correlationId: $this->extractCorrelationId($request),
            providerPaymentId: $request->input('provider_payment_id'),
            providerResponse: $request->input('provider_response'),
        );

        $record = $this->paymentService->updateStatus($dto);

        return $this->response->json([
            'success' => true,
            'data' => new PaymentRecordResource($record),
            'meta' => [
                'correlation_id' => $dto->correlationId,
            ],
        ]);
    }

    /**
     * Извлечь correlation_id из запроса.
     */
    private function extractCorrelationId(Request $request): string
    {
        return (string) ($request->header('X-Correlation-ID')
            ?? $request->input('correlation_id', ''));
    }
}
