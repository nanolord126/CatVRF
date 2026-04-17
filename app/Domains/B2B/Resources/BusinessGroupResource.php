<?php declare(strict_types=1);

namespace App\Domains\B2B\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\B2B\Models\BusinessGroup;

final class BusinessGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var BusinessGroup $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'inn' => $this->inn,
            'kpp' => $this->kpp,
            'legal_address' => $this->legal_address,
            'actual_address' => $this->actual_address,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'commission_percent' => $this->commission_percent,
            'credit_limit' => $this->credit_limit,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
