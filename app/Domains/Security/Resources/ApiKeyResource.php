<?php declare(strict_types=1);

namespace App\Domains\Security\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Security\Models\ApiKey;

final class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ApiKey $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'key_id' => $this->key_id,
            'name' => $this->name,
            'key_preview' => $this->key_preview,
            'permissions' => $this->permissions,
            'ip_whitelist' => $this->ip_whitelist,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'last_used_at' => $this->last_used_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
