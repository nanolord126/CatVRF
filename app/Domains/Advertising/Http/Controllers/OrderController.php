<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final class OrderController
{
    public function __construct(
        private readonly LogManager $log,
        private readonly OrderService $advertisingOrderService,
    ) {}

    public function create(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        $data = $request->all();
        $data['vertical'] = 'advertising';

        $validation = $this->advertisingOrderService->validateOrder($data, $correlationId);

        if (! $validation['valid']) {
            $this->log->channel('audit')->warning('Advertising order validation failed', [
                'reason' => $validation['reason'],
                'fraud_score' => $validation['fraud_score'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Order validation failed',
                'reason' => $validation['reason'],
                'fraud_score' => $validation['fraud_score'] ?? null,
                'correlation_id' => $correlationId,
            ], 400);
        }

        // Process payment through service
        $paymentSuccess = $this->advertisingOrderService->processPayment(
            $data['user_id'],
            $data['amount'],
            $data['payment_method'] ?? 'wallet',
            $correlationId
        );

        if (!$paymentSuccess) {
            return new JsonResponse([
                'error' => 'Payment failed',
                'correlation_id' => $correlationId,
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getDeliveryEstimate(Request $request): JsonResponse
    {
        $address = $request->input('address');
        $estimate = $this->advertisingOrderService->getDeliveryEstimate($address);

        return new JsonResponse([
            'vertical' => 'advertising',
            'delivery_estimate' => $estimate,
            'correlation_id' => $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
        ]);
    }
}
