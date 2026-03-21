<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Domains\Electronics\Models\WarrantyClaim;
use App\Domains\Electronics\Models\ElectronicOrder;
use App\Domains\Electronics\Services\WarrantyService;
use App\Http\Requests\Electronics\StoreOrderRequest;
use App\Http\Requests\Electronics\StoreWarrantyClaimRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ElectronicsOrderController extends BaseApiController
{
    public function __construct(
        private WarrantyService $service,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $orders = ElectronicOrder::where('tenant_id', $tenantId)
                ->with('product')
                ->paginate(20);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Electronics orders list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch orders', 500);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = new ElectronicOrder([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'product_id' => $request->integer('product_id'),
                'client_id' => $request->integer('client_id'),
                'serial_num' => $request->string('serial_num'),
                'imei_num' => $request->string('imei_num'),
                'delivery_date' => Carbon::parse($request->input('delivery_date')),
                'total_price' => 0,
                'status' => 'pending',
            ]);
            $order->save();

            Log::channel('audit')->info('Electronics order created', ['order_id' => $order->id]);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Electronics order creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
        }
    }

    public function claimWarranty(StoreWarrantyClaimRequest $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $claim = $this->service->createWarrantyClaim(
                productId: $request->integer('product_id'),
                clientId: $request->integer('client_id'),
                issueDescription: $request->string('issue_description'),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Warranty claim created', ['claim_id' => $claim->id]);

            return $this->successResponse($claim, 'Warranty claim submitted', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Warranty claim creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to submit claim: ' . $e->getMessage(), 400);
        }
    }
}
