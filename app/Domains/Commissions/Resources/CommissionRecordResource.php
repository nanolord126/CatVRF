<?php declare(strict_types=1);

namespace App\Domains\Commissions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Commissions\Models\CommissionRecord;

final class CommissionRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CommissionRecord $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'vertical' => $this->vertical,
            'amount' => $this->amount,
            'commission' => $this->commission,
            'rate' => $this->rate,
            'operation_type' => $this->operation_type,
            'operation_id' => $this->operation_id,
            'status' => $this->status,
            'payout_scheduled_for' => $this->payout_scheduled_for?->format('Y-m-d H:i:s'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'context' => $this->context,
            'is_pending' => $this->isPending(),
            'is_paid' => $this->isPaid(),
            'is_due_for_payout' => $this->isDueForPayout(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
