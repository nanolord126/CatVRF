<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\OrderService;
use App\Http\Controllers\Api\UniversalOrderController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class OrderController extends UniversalOrderController
{
    public function __construct(
        private readonly OrderService $taxiOrderService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function create(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        
        $data = $request->all();
        $data['vertical'] = 'taxi';
        
        $validation = $this->taxiOrderService->validateOrder($data, $correlationId);
        
        if (!$validation['valid']) {
            $this->logger->warning('Taxi order validation failed', [
                'reason' => $validation['reason'],
                'fraud_score' => $validation['fraud_score'] ?? null,
                'correlation_id' => $correlationId,
            ]);
            
            return response()->json([
                'error' => 'Order validation failed',
                'reason' => $validation['reason'],
                'fraud_score' => $validation['fraud_score'] ?? null,
                'correlation_id' => $correlationId,
            ], 400);
        }

        $response = parent::create($request);
        
        if ($response->status() === 201) {
            $data = $response->getData(true);
            $this->taxiOrderService->sendOrderConfirmation(
                $data['user_id'],
                $data['id'],
                $correlationId
            );
        }
        
        return $response;
    }

    public function getDeliveryEstimate(Request $request): JsonResponse
    {
        $address = $request->input('address');
        $estimate = $this->taxiOrderService->getDeliveryEstimate($address);
        
        return response()->json([
            'vertical' => 'taxi',
            'delivery_estimate' => $estimate,
            'correlation_id' => $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
        ]);
    }
}
