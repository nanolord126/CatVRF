<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Services\OrderService;
use App\Http\Controllers\Api\UniversalOrderController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final class OrderController extends UniversalOrderController
{
    public function __construct(
        private readonly OrderService $travelOrderService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function create(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        
        $data = $request->all();
        $data['vertical'] = 'travel';
        
        $validation = $this->travelOrderService->validateOrder($data, $correlationId);
        
        if (!$validation['valid']) {
            $this->logger->warning('Travel order validation failed', [
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
            $this->travelOrderService->sendOrderConfirmation(
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
        $estimate = $this->travelOrderService->getDeliveryEstimate($address);
        
        return response()->json([
            'vertical' => 'travel',
            'delivery_estimate' => $estimate,
            'correlation_id' => $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
        ]);
    }
}
