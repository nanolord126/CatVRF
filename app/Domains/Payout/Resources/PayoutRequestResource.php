<?php declare(strict_types=1);

namespace App\Domains\Payout\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Payout\Models\PayoutRequest;

final class PayoutRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PayoutRequest $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'business_group_id' => $this->business_group_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'metadata' => $this->metadata,
            'is_pending' => $this->isPending(),
            'is_processing' => $this->isProcessing(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),
            'is_cancelled' => $this->isCancelled(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
