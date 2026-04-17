<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Bonuses\Models\BonusTransaction;

final class BonusTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var BonusTransaction $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'wallet_id' => $this->wallet_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'status' => $this->status,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'hold_until' => $this->hold_until?->format('Y-m-d H:i:s'),
            'credited_at' => $this->credited_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'tags' => $this->tags,
            'is_pending' => $this->isPending(),
            'is_credited' => $this->isCredited(),
            'is_expired' => $this->isExpired(),
            'is_available' => $this->isAvailable(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
