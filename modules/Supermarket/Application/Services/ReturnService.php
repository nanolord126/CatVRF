<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Application\DTOs\CreateReturnData;
use Modules\Supermarket\Application\DTOs\ReturnItemData;
use Modules\Supermarket\Domain\Enums\ReturnStatus;
use Modules\Supermarket\Domain\Exceptions\ReturnPolicyViolationException;
use Modules\Supermarket\Infrastructure\Models\Return;
use Modules\Supermarket\Infrastructure\Models\ReturnItem;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class ReturnService
{
    use WithAuditLogging;
    use WithTelemetry;

    private ReturnPolicyService $policyService;
    private readonly FraudControlService $fraudControl;

    public function __construct(
        ReturnPolicyService $policyService,
        FraudControlService $fraudControl
    ) {
        $this->policyService = $policyService;
        $this->fraudControl = $fraudControl;
    }

    public function createReturn(CreateReturnData $data): Return
    {
        return $this->withSpan(
            'return.create',
            function () use ($data) {
                // Fraud check - first action
                $totalAmount = $data->items->sum(function ($item) {
                    if ($item instanceof ReturnItemData) {
                        return $item->getRefundAmount();
                    }
                    return ($item['price_per_unit'] ?? 0) * ($item['quantity'] ?? 0);
                });

                $this->fraudControl->check([
                    'operation_type' => 'return_create',
                    'vertical' => 'supermarket',
                    'user_id' => $data->buyerId,
                    'amount' => $totalAmount,
                    'order_id' => $data->orderId,
                    'correlation_id' => $data->correlationId,
                ]);

                return DB::transaction(function () use ($data) {
            $violations = [];

            foreach ($data->items as $itemData) {
                if (!($itemData instanceof ReturnItemData)) {
                    $itemData = ReturnItemData::fromArray($itemData);
                }

                $product = \Modules\Supermarket\Infrastructure\Models\Product::find($itemData->productId);
                if (!$product) {
                    $violations[] = [
                        'product_name' => "Product ID {$itemData->productId}",
                        'violations' => ['Product not found'],
                    ];
                    continue;
                }

                $policy = $this->policyService->getPolicyForItem($product->sub_vertical ?? 'default');
                $validation = $this->policyService->validateReturnItem($itemData, $data, $policy);

                if (!$validation['valid']) {
                    $violations[] = [
                        'product_name' => $product->name ?? 'Unknown',
                        'sub_vertical' => $product->sub_vertical ?? 'default',
                        'violations' => $validation['reasons'],
                    ];
                }
            }

            if (!empty($violations)) {
                throw new ReturnPolicyViolationException($violations);
            }

            $totalAmount = $data->items->sum(function ($item) {
                if ($item instanceof ReturnItemData) {
                    return $item->getRefundAmount();
                }
                return ($item['price_per_unit'] ?? 0) * ($item['quantity'] ?? 0);
            });

            $return = Return::create([
                'order_id' => $data->orderId,
                'buyer_id' => $data->buyerId,
                'seller_id' => $data->sellerId,
                'status' => ReturnStatus::PENDING->value,
                'reason_type' => $data->reasonType->value,
                'reason_comment' => $data->comment,
                'is_cold_chain' => $data->isColdChain,
                'return_method' => $data->returnMethod,
                'images' => $data->images,
                'total_amount' => $totalAmount,
                'refund_amount' => $totalAmount,
            ]);

            foreach ($data->items as $itemData) {
                if (!($itemData instanceof ReturnItemData)) {
                    $itemData = ReturnItemData::fromArray($itemData);
                }

                $return->items()->create([
                    'order_item_id' => $itemData->orderItemId,
                    'product_id' => $itemData->productId,
                    'quantity' => $itemData->quantity,
                    'price_per_unit' => $itemData->pricePerUnit,
                    'refund_amount' => $itemData->getRefundAmount(),
                    'condition' => $itemData->condition,
                ]);
            }

            if ($data->isColdChain) {
                $return->update(['priority' => 'high']);
            }

            Log::info('Return created', [
                'return_id' => $return->id,
                'order_id' => $return->order_id,
                'buyer_id' => $return->buyer_id,
                'total_amount' => $return->total_amount,
            ]);

            // Invalidate cache with tags
            \Illuminate\Support\Facades\Cache::tags(['supermarket', 'returns', "user:{$return->buyer_id}"])->flush();

            if ($this->shouldAutoApprove($return)) {
                $this->autoApprove($return);
            }

            $this->logCreated(
                entityType: 'return',
                entityId: $return->id,
                userId: $return->buyer_id,
                context: [
                    'order_id' => $return->order_id,
                    'reason_type' => $return->reason_type,
                    'total_amount' => $return->total_amount,
                    'is_cold_chain' => $return->is_cold_chain,
                    'auto_approved' => $return->auto_approved ?? false,
                ],
            );

            return $return;
            });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_create',
                userId: (string) $data->buyerId,
                correlationId: $data->correlationId ?? null,
            ),
        );
    }

    public function approve(Return $return, string $approvedBy = null): void
    {
        $this->withSpan(
            'return.approve',
            function () use ($return, $approvedBy) {
                // Fraud check before approval
                $this->fraudControl->check([
                    'operation_type' => 'return_approve',
                    'vertical' => 'supermarket',
                    'user_id' => $approvedBy ?? $return->buyer_id,
                    'return_id' => $return->id,
                    'amount' => $return->refund_amount,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                DB::transaction(function () use ($return, $approvedBy) {
            $return->update([
                'status' => ReturnStatus::APPROVED->value,
                'approved_at' => now(),
            ]);

            $this->processRefund($return);
            $this->returnToStock($return);

            Log::info('Return approved', [
                'return_id' => $return->id,
                'approved_by' => $approvedBy,
                'refund_amount' => $return->refund_amount,
            ]);

            // Invalidate cache with tags
            \Illuminate\Support\Facades\Cache::tags(['supermarket', 'returns', "user:{$return->buyer_id}"])->flush();
                });

            $this->logAction(
                action: 'return_approved',
                entityType: 'return',
                entityId: $return->id,
                userId: $approvedBy ?? $return->buyer_id,
                context: [
                    'refund_amount' => $return->refund_amount,
                ],
            );
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_approve',
                userId: $approvedBy ?? (string) $return->buyer_id,
            ),
        );
    }

    public function reject(Return $return, string $reason, string $rejectedBy = null): void
    {
        $this->withSpan(
            'return.reject',
            function () use ($return, $reason, $rejectedBy) {
                // Fraud check before rejection
                $this->fraudControl->check([
                    'operation_type' => 'return_reject',
                    'vertical' => 'supermarket',
                    'user_id' => $rejectedBy ?? $return->buyer_id,
                    'return_id' => $return->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);
                $return->update([
                    'status' => ReturnStatus::REJECTED->value,
                    'reject_reason' => $reason,
                ]);

                $this->logAction(
                    action: 'return_rejected',
                    entityType: 'return',
                    entityId: $return->id,
                    userId: $rejectedBy ?? $return->buyer_id,
                    context: [
                        'reason' => $reason,
                    ],
                );

                Log::info('Return rejected', [
                    'return_id' => $return->id,
                    'rejected_by' => $rejectedBy,
                    'reason' => $reason,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_reject',
                userId: $rejectedBy ?? (string) $return->buyer_id,
            ),
        );
    }

    public function complete(Return $return): void
    {
        $this->withSpan(
            'return.complete',
            function () use ($return) {
                $return->update([
                    'status' => ReturnStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);

                $this->logAction(
                    action: 'return_completed',
                    entityType: 'return',
                    entityId: $return->id,
                    userId: $return->buyer_id,
                );

                Log::info('Return completed', [
                    'return_id' => $return->id,
                ]);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_complete',
                userId: (string) $return->buyer_id,
            ),
        );
    }

    private function shouldAutoApprove(Return $return): bool
    {
        if ($return->is_cold_chain && $return->reason_type === 'spoiled') {
            $order = SupermarketOrder::find($return->order_id);
            if ($order) {
                $hours = Carbon::parse($order->created_at)->diffInHours(now());
                return $hours <= 12;
            }
        }

        return false;
    }

    private function autoApprove(Return $return): void
    {
        $return->update([
            'status' => ReturnStatus::APPROVED->value,
            'approved_at' => now(),
            'auto_approved' => true,
        ]);

        $this->processRefund($return);
        $this->returnToStock($return);

        Log::info('Return auto-approved', [
            'return_id' => $return->id,
        ]);
    }

    private function processRefund(Return $return): void
    {
        $this->withSpan(
            'return.process_refund',
            function () use ($return) {
                try {
                    $paymentAdapter = app(\Modules\Payment\Application\Services\PaymentServiceAdapter::class);
                    $paymentAdapter->refund($return->order_id, $return->refund_amount, 'Return #' . $return->id);
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Refund failed for return', [
                        'return_id' => $return->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_process_refund',
                userId: (string) $return->buyer_id,
            ),
        );
    }

    private function returnToStock(Return $return): void
    {
        foreach ($return->items as $item) {
            if ($item->condition === 'good') {
                $inventoryService = app(\Modules\Inventory\Application\Services\InventoryReservationService::class);
                $inventoryService->returnToStock($item->product_id, $item->quantity);
            }
        }
    }
}
