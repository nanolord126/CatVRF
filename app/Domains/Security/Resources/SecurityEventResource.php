<?php declare(strict_types=1);

namespace App\Domains\Security\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Security\Models\SecurityEvent;

final class SecurityEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SecurityEvent $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'event_type' => $this->event_type,
            'severity' => $this->severity,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlation_id,
            'is_critical' => $this->isCritical(),
            'is_warning' => $this->isWarning(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
