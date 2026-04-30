<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Application\DTOs\CreateReturnData;
use Modules\Supermarket\Application\DTOs\ReturnItemData;
use Modules\Supermarket\Infrastructure\Models\ReturnPolicy;
use Modules\Supermarket\Infrastructure\Models\ReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class ReturnPolicyService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    public function getPolicyForItem(string $subVertical): ReturnPolicy
    {
        return $this->withSpan(
            'return_policy.get_for_item',
            function () use ($subVertical) {
                // Fraud check for policy retrieval
                $this->fraudControl->check([
                    'operation_type' => 'return_policy_get',
                    'vertical' => 'supermarket',
                    'sub_vertical' => $subVertical,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $policy = ReturnPolicy::bySubVertical($subVertical)
                    ->active()
                    ->first();

                if (!$policy) {
                    return $this->getDefaultPolicy();
                }

                return $policy;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'return_policy_get_for_item',
            ),
        );
    }

    public function validateReturnItem(
        ReturnItemData $item,
        CreateReturnData $returnData,
        ReturnPolicy $policy
    ): array {
        $reasons = [];

        $order = \Modules\Supermarket\Infrastructure\Models\SupermarketOrder::find($returnData->orderId);
        if (!$order) {
            $reasons[] = 'Order not found';
            return ['valid' => false, 'reasons' => $reasons];
        }

        $daysPassed = Carbon::parse($order->created_at)->diffInDays(now());
        if ($daysPassed > $policy->max_days) {
            $reasons[] = "Return period exceeded ({$policy->max_days} days allowed)";
        }

        if (!$policy->allowsReason($returnData->reasonType->value)) {
            $reasons[] = 'Return reason not allowed for this category';
        }

        if ($policy->cold_chain_only_defect && $returnData->isColdChain) {
            if ($returnData->reasonType->value !== 'spoiled' && $item->condition !== 'spoiled') {
                $reasons[] = 'Cold chain items can only be returned if spoiled';
            }
        }

        if ($policy->requires_photo && empty($returnData->images)) {
            $reasons[] = 'Photo evidence required for this category';
        }

        return [
            'valid' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    public function canReturn(ReturnItem $item, \Modules\Supermarket\Infrastructure\Models\Return $return): bool
    {
        $policy = $this->getPolicyForItem($item->product->sub_vertical ?? 'default');

        $validation = $this->validateReturnItem(
            new ReturnItemData(
                orderItemId: $item->order_item_id,
                productId: $item->product_id,
                quantity: $item->quantity,
                pricePerUnit: $item->price_per_unit,
                condition: $item->condition,
            ),
            new CreateReturnData(
                orderId: $return->order_id,
                buyerId: $return->buyer_id,
                sellerId: $return->seller_id,
                reasonType: $return->getReasonTypeEnum(),
                comment: $return->reason_comment,
                isColdChain: $return->is_cold_chain,
                returnMethod: $return->return_method,
                images: $return->images,
            ),
            $policy
        );

        return $validation['valid'];
    }

    private function getDefaultPolicy(): ReturnPolicy
    {
        return new ReturnPolicy([
            'sub_vertical' => 'default',
            'vertical' => 'supermarket',
            'max_days' => 3,
            'allowed_reasons' => ['spoiled', 'wrong_item'],
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => false,
            'max_refund_percent' => 100,
            'is_active' => true,
        ]);
    }
}
