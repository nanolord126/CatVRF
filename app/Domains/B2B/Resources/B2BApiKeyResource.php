<?php declare(strict_types=1);

namespace App\Domains\B2B\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\B2B\Models\B2BApiKey;

final class B2BApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var B2BApiKey $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'business_group_id' => $this->business_group_id,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'last_used_at' => $this->last_used_at?->format('Y-m-d H:i:s'),
            'last_ip' => $this->last_ip,
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
